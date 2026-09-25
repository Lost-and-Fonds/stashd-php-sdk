<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\AcquisitionOptions;
use Stashd\PluginSdk\ArtifactRole;
use Stashd\PluginSdk\DiscoveryIntent;
use Stashd\PluginSdk\InputOption;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\HostCapabilityException;
use Stashd\PluginSdk\InvalidPluginResultException;
use Stashd\PluginSdk\MediaKind;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\Runtime\RuntimeFrameCodec;
use Stashd\PluginSdk\WireMapper;
use Stashd\PluginSdk\CapabilityUnavailableException;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\PluginFailureException;
use Throwable;

final class InputPluginServer
{
    /** @param Closure(PluginContext):InputPlugin $factory */
    public function __construct(private Closure $factory) {}

    public function run(): never
    {
        RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => 'sdk-hello', 'kind' => 'request', 'method' => 'hello', 'params' => ['min' => 1, 'max' => 1]]);
        $this->handshake();
        $plugin = ($this->factory)($this->context());

        while (($message = RuntimeFrameCodec::read(STDIN, 3600.0)) !== null) {
            $id = is_string($message['id'] ?? null) ? $message['id'] : '';

            try {
                $result = $this->dispatch($plugin, is_string($message['method'] ?? null) ? $message['method'] : '', RuntimeFrameCodec::object($message['params'] ?? null));
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'result' => $result]);
            } catch (PluginFailureException $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => WireMapper::pluginFailure($exception->failure)]);
            } catch (CapabilityUnavailableException $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => WireMapper::pluginFailure(new PluginFailure(PluginErrorCode::Unavailable, new PluginError($exception->getMessage(), true)))]);
            } catch (Throwable $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => WireMapper::pluginFailure(new PluginFailure(PluginErrorCode::Failed, new PluginError($exception->getMessage(), false)))]);
            }
        }
        exit(0);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int|string, mixed>
     */
    private function dispatch(InputPlugin $plugin, string $method, array $params): array
    {
        return match ($method) {
            'input.resolve' => WireMapper::resolvedInput($plugin->resolve(WireMapper::sourceDescriptorFromWire($this->requiredList($params, 'source')))),
            'input.discover' => WireMapper::discoveredItems($plugin->discover(
                $this->requiredString($params, 'input-id'),
                DiscoveryIntent::from($this->requiredString($params, 'intent')),
                $this->options($this->requiredList($params, 'options')),
            )),
            'input.acquire' => WireMapper::acquisition($plugin->acquire(
                WireMapper::discoveredItemFromWire($this->requiredObject($params, 'item')),
                $this->acquisitionOptions($this->requiredObject($params, 'options')),
            )),
            default => throw new \RuntimeException('unknown plugin method: ' . $method),
        };
    }

    /** @param array<string, mixed> $values */
    private function acquisitionOptions(array $values): AcquisitionOptions
    {
        if (! array_key_exists('requested-roles', $values) || ! array_key_exists('credentials', $values)) {
            throw new InvalidPluginResultException('acquisition options omit a required option field');
        }

        return new AcquisitionOptions(
            MediaKind::from($this->requiredString($values, 'media-kind')),
            $this->options($this->requiredList($values, 'options')),
            $this->artifactRoles($values['requested-roles']),
            $this->credentials($values['credentials']),
        );
    }

    /** @return list<ArtifactRole>|null */
    private function artifactRoles(mixed $roles): ?array
    {
        if ($roles === null) {
            return null;
        }

        if (! is_array($roles) || ! array_is_list($roles)) {
            throw new InvalidPluginResultException('requested_roles must be a list');
        }

        return array_map(static fn(mixed $role): ArtifactRole => ArtifactRole::from(is_string($role) ? $role : ''), $roles);
    }

    /** @return array<string, string>|null */
    private function credentials(mixed $credentials): ?array
    {
        if ($credentials === null) {
            return null;
        }

        if (! is_array($credentials) || ! array_is_list($credentials)) {
            throw new InvalidPluginResultException('credentials must be a list');
        }
        $result = [];

        foreach ($credentials as $credential) {
            if (! is_array($credential) || ! is_string($credential['key'] ?? null) || ! is_string($credential['value'] ?? null) || isset($result[$credential['key']])) {
                throw new InvalidPluginResultException('credential is invalid');
            }
            $result[$credential['key']] = $credential['value'];
        }

        return $result;
    }

    private function context(): PluginContext
    {
        $call = function (string $method, array $params): array {
            static $next = 1;
            /** @var int $next */
            $id = 'sdk-' . $next++;
            RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'request', 'method' => $method, 'params' => $params]);

            while (($message = RuntimeFrameCodec::read(STDIN, 300.0)) !== null) {
                if (($message['kind'] ?? null) === 'notification') {
                    continue;
                }

                if (($message['id'] ?? null) !== $id) {
                    continue;
                }

                if (is_array($message['error'] ?? null)) {
                    if (is_string($message['error']['tag'] ?? null)) {
                        throw new HostCapabilityException($method, $message['error']['tag'], $message['error']['value'] ?? null);
                    }

                    throw new \RuntimeException(is_string($message['error']['message'] ?? null) ? $message['error']['message'] : 'capability failed');
                }

                return is_array($message['result'] ?? null) ? $message['result'] : [];
            }

            throw new \RuntimeException('host closed capability channel');
        };

        return new PluginContext(new RuntimeLogger($call), new RuntimeProgressReporter($call), new RuntimeHttpClient($call), new RuntimeStagingArea($call, false), new RuntimeHelperRunner($call), '/plugin-data', '/staging');
    }

    private function handshake(): void
    {
        $response = RuntimeFrameCodec::read(STDIN, 30.0);

        if ($response === null || ($response['id'] ?? null) !== 'sdk-hello' || ($response['kind'] ?? null) !== 'response') {
            throw new \RuntimeException('plugin RPC handshake failed');
        }
        $result = RuntimeFrameCodec::object($response['result'] ?? null);

        if (($result['protocol'] ?? null) !== 1 || ($result['min'] ?? null) !== 1 || ($result['max'] ?? null) !== 1) {
            throw new \RuntimeException('unsupported plugin RPC protocol');
        }
    }

    /**
     * @param mixed $values
     * @return list<InputOption>
     */
    private function options(mixed $values): array
    {
        if (! is_array($values) || ! array_is_list($values)) {
            throw new InvalidPluginResultException('input options must be a list');
        }
        $options = [];

        foreach ($values as $value) {
            if (! is_array($value) || ! is_string($value['key'] ?? null) || ! is_array($value['value'] ?? null)) {
                throw new InvalidPluginResultException('input option is malformed');
            }
            $options[] = new InputOption($value['key'], OptionValue::fromWire($value['value']));
        }

        return $options;
    }

    /** @param array<string, mixed> $values */
    private function requiredString(array $values, string $key): string
    {
        if (! is_string($values[$key] ?? null)) {
            throw new InvalidPluginResultException("required string field is missing or malformed: {$key}");
        }

        return $values[$key];
    }

    /** @param array<string, mixed> $values
     * @return list<array<string, mixed>>
     */
    private function requiredList(array $values, string $key): array
    {
        if (! array_key_exists($key, $values) || ! is_array($values[$key]) || ! array_is_list($values[$key])) {
            throw new InvalidPluginResultException("required list field is missing or malformed: {$key}");
        }

        $result = [];

        foreach ($values[$key] as $value) {
            if (! is_array($value)) {
                throw new InvalidPluginResultException("required list field contains a malformed record: {$key}");
            }
            $result[] = RuntimeFrameCodec::object($value);
        }

        return $result;
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function requiredObject(array $values, string $key): array
    {
        if (! is_array($values[$key] ?? null) || array_is_list($values[$key])) {
            throw new InvalidPluginResultException("required record is missing or malformed: {$key}");
        }

        return RuntimeFrameCodec::object($values[$key]);
    }
}
