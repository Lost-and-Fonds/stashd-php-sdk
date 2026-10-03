<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use BackedEnum;
use JsonException;
use ReflectionObject;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\OwnedResource;
use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;
use Stashd\PluginSdk\Runtime\Resource\RemoteHelperProcess;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagingArea;
use Stashd\PluginSdk\Shared\Unsigned64;
use stdClass;

/**
 * Validates typed WIT values recursively while committing resource ownership at the RPC boundary.
 */
final class ResourceValueCodec
{
    /**
     * Build the sole canonical resource marker; ownership is supplied by its WIT position.
     */
    public static function handle(string $type, string $id): stdClass
    {
        return (object) ['$resource' => (object) ['type' => $type, 'id' => $id]];
    }

    /**
     * Decode a host-owned result, installing each nested handle before exposing it to PHP.
     * @param array<string, mixed> $schema
     */
    public static function decode(mixed $wire, array $schema, string $interface, Invocation $invocation, ?string $writerId = null): mixed
    {
        $kind = $schema['kind'];

        if ($kind === 'option') {
            return $wire === null ? null : self::decode($wire, self::node($schema['value']), $interface, $invocation, $writerId);
        }

        if ($kind === 'list') {
            return array_map(static fn(mixed $element): mixed => self::decode($element, self::node($schema['value']), $interface, $invocation, $writerId), Values::list($wire));
        }

        if ($kind === 'result') {
            if (!$wire instanceof stdClass) {
                throw new ProtocolViolation('Expected WIT result');
            }

            $branch = property_exists($wire, 'ok') ? 'ok' : 'error';
            Values::record($wire, [$branch]);

            return (object) [$branch => $schema[$branch] === null ? self::unit($wire->{$branch}) : self::decode($wire->{$branch}, self::node($schema[$branch]), $interface, $invocation, $writerId)];
        }

        if ($kind === 'borrow') {
            $resource = self::resourceMarker($wire, self::node($schema['value']), $interface);
            $invocation->resources->requireOwned($invocation->id, self::name($resource->id), self::name($resource->type));

            return $wire;
        }

        if ($kind === 'scalar') {
            return match ($schema['name']) {
                'string' => Values::text($wire),
                'bool' => Values::boolean($wire),
                'u64' => Values::unsigned($wire),
                's64' => Values::signed($wire),
                'f32', 'f64' => Values::floating($schema['name'], $wire),
                default => Values::integer(self::name($schema['name']), $wire),
            };
        }

        if ($kind !== 'named') {
            throw new ProtocolViolation('Unknown WIT value shape');
        }

        [$owner, $name, $definition] = self::definition(self::name($schema['name']), $interface);
        $type = 'stashd:plugin/' . $owner . '.' . $name;

        if ($definition['kind'] === 'resource') {
            $marker = self::marker($wire, $type);

            if ($type === 'stashd:plugin/io-host.staged-writer' && $writerId !== null) {
                $invocation->resources->returnTransferred($invocation->id, self::name($marker->id), $type);
            } else {
                $invocation->resources->accept($invocation->id, self::name($marker->id), $type);
            }

            return match ($type) {
                'stashd:plugin/io-host.staged-writer' => new RemoteStagedWriter($invocation, self::name($marker->id)),
                'stashd:plugin/io-host.byte-stream' => new RemoteByteStream($invocation, self::name($marker->id)),
                'stashd:plugin/io-host.helper-process' => new RemoteHelperProcess($invocation, self::name($marker->id), $writerId),
                'stashd:plugin/io-host.staging-area' => new RemoteStagingArea($invocation, self::name($marker->id)),
                default => self::unsupportedResource($type),
            };
        }

        if ($definition['kind'] === 'record') {
            $fields = self::sequence(self::mapping($definition['value'])['fields']);
            $record = Values::record($wire, array_map(static fn(mixed $field): string => self::name(self::mapping($field)['name']), $fields));
            $arguments = [];

            if ($owner === 'io-host' && $name === 'helper-exit' && $record->output !== null) {
                $marker = self::marker($record->output, 'stashd:plugin/io-host.staged-writer');

                if ($writerId === null || $marker->id !== $writerId) {
                    throw new ProtocolViolation('Helper returned a writer not owned by this process');
                }

                $invocation->resources->requireTransferred($invocation->id, $writerId, 'stashd:plugin/io-host.staged-writer');
            }

            foreach ($fields as $field) {
                $field = self::mapping($field);
                $fieldName = self::name($field['name']);
                $arguments[] = self::decode($record->{$fieldName}, self::node($field['type']), $owner, $invocation, $writerId);
            }

            $class = self::contractClass($owner, $name);

            return new $class(...$arguments);
        }

        if ($definition['kind'] === 'variant') {
            [$tag, $payload, $hasPayload] = self::variant($wire);

            foreach (self::sequence(self::mapping($definition['value'])['values']) as $case) {
                $case = self::mapping($case);

                if ($case['name'] !== $tag) {
                    continue;
                }

                if (($case['type'] !== null) !== $hasPayload) {
                    throw new ProtocolViolation('Variant payload presence does not match WIT case');
                }

                $class = self::contractClass($owner, $name . self::pascal($tag));

                return $hasPayload ? new $class(self::decode($payload, self::node($case['type']), $owner, $invocation, $writerId)) : new $class();
            }

            throw new ProtocolViolation('Unknown WIT variant case');
        }

        $class = self::contractClass($owner, $name);

        return $class::tryFrom(Values::text($wire)) ?? throw new ProtocolViolation('Unknown WIT enum case');
    }

    /**
     * Validate and encode nested owned arguments before the invocation commits their transfer.
     * @param array<string, mixed> $schema
     * @param list<array{string, string}> $transfers
     */
    public static function encode(mixed $value, array $schema, string $interface, Invocation $invocation, array &$transfers): mixed
    {
        $kind = $schema['kind'];

        if ($kind === 'option') {
            return $value === null ? null : self::encode($value, self::node($schema['value']), $interface, $invocation, $transfers);
        }

        if ($kind === 'list') {
            $encoded = [];

            foreach (Values::list($value) as $element) {
                $encoded[] = self::encode($element, self::node($schema['value']), $interface, $invocation, $transfers);
            }

            return $encoded;
        }

        if ($kind === 'scalar') {
            return match ($schema['name']) {
                'string' => Values::text($value),
                'bool' => Values::boolean($value),
                'u64' => $value instanceof \Stashd\PluginSdk\Shared\Unsigned64 ? $value->decimal : Values::unsigned($value)->decimal,
                's64' => (string) Values::signed($value),
                'f32', 'f64' => Values::floating($schema['name'], $value),
                default => Values::integer(self::name($schema['name']), $value),
            };
        }

        if ($kind === 'borrow') {
            $type = self::resourceType(self::node($schema['value']), $interface);

            if (!$value instanceof OwnedResource) {
                throw new ProtocolViolation('Borrow requires a resource proxy');
            }

            return self::handle($type, $value->resourceId($invocation->resources, $invocation->id, $type));
        }

        if ($kind !== 'named') {
            throw new ProtocolViolation('Resource-aware encoding requires a declared named or container value');
        }

        [$owner, $name, $definition] = self::definition(self::name($schema['name']), $interface);
        $type = 'stashd:plugin/' . $owner . '.' . $name;

        if ($definition['kind'] === 'resource') {
            if (!$value instanceof OwnedResource) {
                throw new ProtocolViolation('Owned resource requires an invocation-bound proxy');
            }

            $id = $value->resourceId($invocation->resources, $invocation->id, $type);
            $transfers[] = [$id, $type];

            return self::handle($type, $id);
        }

        if ($definition['kind'] === 'record') {
            $class = self::contractClass($owner, $name);

            if (!$value instanceof $class) {
                throw new ProtocolViolation('Record does not match its WIT type');
            }

            $record = new stdClass();

            foreach (self::sequence(self::mapping($definition['value'])['fields']) as $field) {
                $field = self::mapping($field);
                $fieldName = self::name($field['name']);
                $property = lcfirst(self::pascal($fieldName));
                $record->{$fieldName} = self::encode($value->{$property}, self::node($field['type']), $owner, $invocation, $transfers);
            }

            return $record;
        }

        if ($definition['kind'] === 'variant') {
            foreach (self::sequence(self::mapping($definition['value'])['values']) as $case) {
                $case = self::mapping($case);
                $caseName = self::name($case['name']);
                $class = self::contractClass($owner, $name . self::pascal($caseName));

                if (!$value instanceof $class) {
                    continue;
                }

                if ($case['type'] === null) {
                    return $caseName;
                }

                $property = (new ReflectionObject($value))->getProperty('value')->getValue($value);

                return (object) [
                    'tag' => $caseName,
                    'value' => self::encode($property, self::node($case['type']), $owner, $invocation, $transfers),
                ];
            }

            throw new ProtocolViolation('Variant does not match its WIT case');
        }

        if (!$value instanceof BackedEnum || $value::class !== self::contractClass($owner, $name)) {
            throw new ProtocolViolation('Enum does not match its WIT type');
        }

        return $value->value;
    }

    /**
     * Require a nested schema node rather than trusting untyped JSON snapshot data.
     * @return array<string, mixed>
     */
    private static function node(mixed $schema): array
    {
        if (!is_array($schema) || !is_string($schema['kind'] ?? null) || array_filter(array_keys($schema), 'is_string') !== array_keys($schema)) {
            throw new ProtocolViolation('Invalid pinned WIT schema node');
        }

        return array_combine(array_map(static fn(mixed $key): string => self::name($key), array_keys($schema)), array_values($schema));
    }

    /**
     * Require one schema name or field name to be text.
     */
    private static function name(mixed $name): string
    {
        if (!is_string($name)) {
            throw new ProtocolViolation('Invalid pinned WIT schema name');
        }

        return $name;
    }

    /**
     * Require an ordered schema sequence before iterating fields or cases.
     * @return list<mixed>
     */
    private static function sequence(mixed $values): array
    {
        if (!is_array($values) || !array_is_list($values)) {
            throw new ProtocolViolation('Invalid pinned WIT schema sequence');
        }

        return $values;
    }

    /**
     * Require a schema interface or record definition to be an object-shaped array.
     * @return array<string, mixed>
     */
    private static function mapping(mixed $value): array
    {
        if (!is_array($value) || array_filter(array_keys($value), 'is_string') !== array_keys($value)) {
            throw new ProtocolViolation('Invalid pinned WIT schema definition');
        }

        return array_combine(array_map(static fn(mixed $key): string => self::name($key), array_keys($value)), array_values($value));
    }

    /**
     * Return the exact nested resource marker after rejecting extra keys and wrong types.
     * @param array<string, mixed> $schema
     */
    private static function resourceMarker(mixed $wire, array $schema, string $interface): stdClass
    {
        return self::marker($wire, self::resourceType($schema, $interface));
    }

    /**
     * Resolve a declared named resource type without interpreting its opaque ID.
     * @param array<string, mixed> $schema
     */
    private static function resourceType(array $schema, string $interface): string
    {
        if ($schema['kind'] !== 'named') {
            throw new ProtocolViolation('Borrow must refer to a named resource');
        }

        [$owner, $name, $definition] = self::definition(self::name($schema['name']), $interface);

        if ($definition['kind'] !== 'resource') {
            throw new ProtocolViolation('Borrow must refer to a resource');
        }

        return 'stashd:plugin/' . $owner . '.' . $name;
    }

    /**
     * Verify the exact canonical resource JSON marker and expected WIT type.
     */
    private static function marker(mixed $wire, string $type): stdClass
    {
        $outer = Values::record($wire, ['$resource']);
        $marker = Values::record($outer->{'$resource'}, ['type', 'id']);

        if (Values::text($marker->type) !== $type || Values::text($marker->id) === '') {
            throw new ProtocolViolation('Resource marker has wrong type or empty ID');
        }

        return $marker;
    }

    /**
     * Resolve a named schema entry from the pinned frozen WIT snapshot.
     * @return array{string, string, array{kind: string, value: mixed}}
     */
    private static function definition(string $name, string $interface): array
    {
        $schema = self::schema();
        $uses = self::mapping(self::interface($schema, $interface)['uses']);
        $owner = self::name($uses[$name] ?? $interface);
        $definition = self::interface($schema, $owner);

        foreach (['records' => 'record', 'variants' => 'variant', 'enums' => 'enum'] as $section => $kind) {
            $values = self::mapping($definition[$section]);

            if (isset($values[$name])) {
                return [$owner, $name, ['kind' => $kind, 'value' => $values[$name]]];
            }
        }

        foreach (self::sequence($definition['resources']) as $resource) {
            $resource = self::mapping($resource);

            if ($resource['name'] === $name) {
                return [$owner, $name, ['kind' => 'resource', 'value' => $resource]];
            }
        }

        throw new ProtocolViolation('Unknown WIT named type');
    }

    /**
     * Find one canonical interface without granting unrelated worlds or capabilities.
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private static function interface(array $schema, string $name): array
    {
        foreach (self::sequence($schema['contracts']) as $contract) {
            $interfaces = self::mapping(self::mapping($contract)['interfaces']);

            if (isset($interfaces[$name])) {
                return self::mapping($interfaces[$name]);
            }
        }

        throw new ProtocolViolation('Unknown WIT interface');
    }

    /**
     * Load the checked-in immutable schema without inferring identity from Composer metadata.
     * @return array<string, mixed>
     */
    private static function schema(): array
    {
        static $schema = null;

        if ($schema === null) {
            $json = file_get_contents(dirname(__DIR__, 3) . '/resources/contract/wit-schema.json');

            if ($json === false) {
                throw new ProtocolViolation('Pinned WIT schema is unreadable');
            }

            try {
                $schema = self::mapping(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
            } catch (JsonException $error) {
                throw new ProtocolViolation('Pinned WIT schema is invalid JSON', previous: $error);
            }

            if ($schema['package'] !== 'stashd:plugin@0.18.0') {
                throw new ProtocolViolation('Unexpected WIT package identity');
            }
        }

        return self::mapping($schema);
    }

    /**
     * Map WIT hyphenated type names to their generated PHP class names.
     */
    private static function pascal(string $name): string
    {
        return implode('', array_map(ucfirst(...), explode('-', $name)));
    }

    /**
     * Build the exact generated contract class for an interface member.
     */
    private static function contractClass(string $interface, string $name): string
    {
        return 'Stashd\\PluginSdk\\Contract\\' . self::pascal($interface) . '\\' . self::pascal($name);
    }

    /**
     * Decode a canonical payloadless variant or its exact tagged payload.
     * @return array{string, mixed, bool}
     */
    private static function variant(mixed $wire): array
    {
        if (is_string($wire)) {
            return [$wire, null, false];
        }

        $record = Values::record($wire, ['tag', 'value']);

        return [Values::text($record->tag), $record->value, true];
    }

    /**
     * Reject non-null data at a canonical unit result position.
     */
    private static function unit(mixed $wire): null
    {
        if ($wire !== null) {
            throw new ProtocolViolation('WIT unit must be null');
        }

        return null;
    }

    /**
     * Fail explicitly when this exact boundary lacks a proxy for another host resource type.
     */
    private static function unsupportedResource(string $type): never
    {
        throw new ProtocolViolation('Unsupported host resource proxy type: ' . $type);
    }
}
