<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final class WireMapper
{
    /** @return array<string, mixed> */
    public static function publishRequest(PublishRequest $request): array
    {
        return [
            'reference' => $request->reference,
            'settings' => array_map([self::class, 'setting'], $request->settings),
            'sources' => array_map([self::class, 'source'], $request->sources),
            'items' => array_map([self::class, 'item'], $request->items),
        ];
    }

    /** @return array<string, mixed> */
    public static function publication(Publication $publication): array
    {
        self::assertUnsigned($publication->artifact->sizeBytes);

        return [
            'artifact' => ['reference' => $publication->artifact->reference, 'media-type' => $publication->artifact->mediaType, 'size-bytes' => $publication->artifact->sizeBytes],
            'files' => array_map(static fn(PublishedFile $file): array => ['item-id' => $file->itemId, 'source-reference' => $file->sourceReference, 'relative-path' => $file->relativePath], $publication->files),
            'published-metadata' => array_map([self::class, 'setting'], $publication->publishedMetadata),
        ];
    }

    /** @return array<string, mixed> */
    public static function pluginFailure(PluginFailure $failure): array
    {
        return ['tag' => $failure->code->value, 'value' => ['message' => $failure->error->message, 'retryable' => $failure->error->retryable]];
    }

    /** @return array<string, mixed> */
    public static function stagedArtifact(?StagedArtifact $artifact): array
    {
        if ($artifact === null) {
            throw new InvalidPluginResultException('staging did not return an artifact');
        }

        if ($artifact->role !== null && ! in_array($artifact->role, ['primary', 'captions', 'artwork', 'metadata'], true)) {
            throw new InvalidPluginResultException('staging returned an unknown artifact role');
        }

        if ($artifact->sizeBytes < 0) {
            throw new InvalidPluginResultException('staging returned a negative artifact size');
        }

        return ['reference' => $artifact->reference, 'role' => $artifact->role, 'media-type' => $artifact->mediaType, 'size-bytes' => $artifact->sizeBytes];
    }

    /** @param array<array-key, mixed> $data */
    public static function publishRequestFromWire(array $data): PublishRequest
    {
        return new PublishRequest(
            self::requiredString($data, 'reference'),
            self::settingsFromWire(self::requiredField($data, 'settings')),
            array_map(static fn(array $source): Source => new Source(self::requiredString($source, 'reference'), self::settingsFromWire(self::requiredField($source, 'settings'))), self::listOfArrays(self::requiredField($data, 'sources'))),
            array_map(static fn(array $item): Item => new Item(
                self::requiredString($item, 'id'),
                self::requiredString($item, 'title'),
                array_map(static fn(array $resource): ItemResource => new ItemResource(
                    self::requiredString($resource, 'reference'),
                    self::requiredString($resource, 'kind'),
                    self::optionalString(self::requiredField($resource, 'derivation-key')),
                    self::optionalString(self::requiredField($resource, 'url')),
                    self::optionalString(self::requiredField($resource, 'media-type')),
                    self::unsignedValue(self::requiredField($resource, 'size-bytes')),
                ), self::listOfArrays(self::requiredField($item, 'resources'))),
                self::optionalString(self::requiredField($item, 'source-reference')),
                self::optionalString(self::requiredField($item, 'description')),
                self::optionalString(self::requiredField($item, 'published-at')),
                self::optionalU32Field($item, 'duration-seconds'),
            ), self::listOfArrays(self::requiredField($data, 'items'))),
        );
    }

    /** @param array<array-key, mixed> $data */
    public static function operationRequestFromWire(array $data): OperationRequest
    {
        return new OperationRequest(self::requiredString($data, 'name'), self::settingsFromWire(self::requiredField($data, 'settings')), self::settingsFromWire(self::requiredField($data, 'payload')));
    }

    /** @param array<string, mixed> $data */
    public static function publicationFromWire(array $data): Publication
    {
        $artifact = self::requiredArray($data, 'artifact');

        return new Publication(
            new Artifact(self::requiredString($artifact, 'reference'), self::optionalString(self::requiredField($artifact, 'media-type')), self::unsignedValue(self::requiredField($artifact, 'size-bytes'))),
            array_map(static fn(array $file): PublishedFile => new PublishedFile(self::requiredString($file, 'item-id'), self::requiredString($file, 'source-reference'), self::requiredString($file, 'relative-path')), self::listOfArrays(self::requiredField($data, 'files'))),
            self::settingsFromWire(self::requiredField($data, 'published-metadata')),
        );
    }

    /** @return array<string,mixed> */
    public static function preparation(Preparation $preparation): array
    {
        return ['artifacts' => array_map(static function (DerivedArtifact $artifact): array {
            self::assertUnsigned($artifact->sizeBytes);

            return [
                'item-id' => $artifact->itemId,
                'reference' => $artifact->reference,
                'derived-from-reference' => $artifact->derivedFromReference,
                'derivation-key' => $artifact->derivationKey,
                'kind' => $artifact->kind,
                'media-type' => $artifact->mediaType,
                'size-bytes' => $artifact->sizeBytes,
            ];
        }, $preparation->artifacts)];
    }

    /** @return array<string,mixed> */
    public static function operationResult(OperationResult $result): array
    {
        return ['choices' => array_map(static fn(Choice $choice): array => ['value' => $choice->value, 'label' => $choice->label], $result->choices), 'values' => array_map([self::class, 'setting'], $result->values)];
    }

    /** @return array<string,mixed> */
    public static function resolvedInput(ResolvedInput $input): array
    {
        self::assertU32($input->estimatedItemCount);
        self::assertUnsigned($input->sizeBytes);

        return ['id' => $input->id, 'canonical-reference' => $input->canonicalReference, 'kind' => $input->kind, 'title' => $input->title, 'artwork-reference' => $input->artworkReference, 'estimated-item-count' => $input->estimatedItemCount, 'size-bytes' => $input->sizeBytes, 'size-estimated' => $input->sizeEstimated];
    }

    /** @return list<array{key:string,value:array{tag:string,value:bool|int|string}}> */
    public static function sourceDescriptor(SourceDescriptor $source): array
    {
        $values = [];

        foreach ($source->values as $key => $value) {
            $values[] = ['key' => $key, 'value' => $value->toWire()];
        }

        return $values;
    }

    public static function sourceDescriptorFromWire(mixed $values): SourceDescriptor
    {
        $source = [];

        foreach (self::listOfArrays($values) as $value) {
            $source[self::requiredString($value, 'key')] = OptionValue::fromWire(self::requiredArray($value, 'value'));
        }

        return new SourceDescriptor($source);
    }

    /**
     * @param list<DiscoveredItem> $items
     * @return list<array<string, mixed>>
     */
    public static function discoveredItems(array $items): array
    {
        return array_map(self::discoveredItem(...), $items);
    }

    /** @return array<string, mixed> */
    public static function discoveredItem(DiscoveredItem $item): array
    {
        self::assertU32($item->durationSeconds);
        self::assertUnsigned($item->sizeBytes);

        return ['id' => $item->id, 'reference' => $item->reference, 'title' => $item->title, 'description' => $item->description, 'published-at' => $item->publishedAt, 'artwork-reference' => $item->artworkReference, 'duration-seconds' => $item->durationSeconds, 'kind' => $item->kind, 'size-bytes' => $item->sizeBytes, 'size-estimated' => $item->sizeEstimated, 'upstream-state' => $item->upstreamState];
    }

    /** @return array<string,mixed> */
    public static function acquisition(AcquisitionResult $result): array
    {
        return [
            'artifacts' => array_map(static fn(StagedArtifact $artifact): array => self::stagedArtifact($artifact), $result->artifacts),
            'unavailable' => array_map(static fn(UnavailableArtifact $artifact): array => ['role' => $artifact->role->value, 'permanent' => $artifact->permanent, 'message' => $artifact->message], $result->unavailable),
        ];
    }

    /** @param array<string, mixed> $item */
    public static function discoveredItemFromWire(array $item): DiscoveredItem
    {
        return new DiscoveredItem(
            self::requiredString($item, 'id'),
            self::requiredString($item, 'reference'),
            self::requiredString($item, 'title'),
            self::optionalString(self::requiredField($item, 'description')),
            self::optionalString(self::requiredField($item, 'published-at')),
            self::optionalString(self::requiredField($item, 'artwork-reference')),
            self::optionalU32Field($item, 'duration-seconds'),
            self::optionalString(self::requiredField($item, 'kind')),
            self::optionalUnsignedField($item, 'size-bytes'),
            self::requiredBool($item, 'size-estimated'),
            self::optionalString(self::requiredField($item, 'upstream-state')),
        );
    }

    /**
     * @param mixed $values
     * @return list<Setting>
     */
    private static function settingsFromWire(mixed $values): array
    {
        $result = [];

        foreach (self::listOfArrays($values) as $setting) {
            $value = self::requiredArray($setting, 'value');
            $result[] = new Setting(self::requiredString($setting, 'key'), OptionValue::fromWire($value));
        }

        return $result;
    }

    /**
     * @param mixed $values
     * @return list<array<string, mixed>>
     */
    private static function listOfArrays(mixed $values): array
    {
        if (! is_array($values) || ! array_is_list($values)) {
            throw new InvalidPluginResultException('expected a list');
        }

        $result = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                throw new InvalidPluginResultException('list entry is not an object');
            }
            /** @var array<string,mixed> $value */
            $result[] = $value;
        }

        return $result;
    }

    /** @param array<array-key, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        if (! is_string($data[$key] ?? null)) {
            throw new InvalidPluginResultException("required string field is missing: {$key}");
        }

        return $data[$key];
    }

    private static function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidPluginResultException('optional string field is malformed');
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<string, mixed>
     */
    private static function requiredArray(array $data, string $key): array
    {
        if (! is_array($data[$key] ?? null)) {
            throw new InvalidPluginResultException("required object field is missing: {$key}");
        }

        $result = [];

        foreach ($data[$key] as $name => $value) {
            if (! is_string($name)) {
                throw new InvalidPluginResultException("object field contains a non-string key: {$key}");
            }
            $result[$name] = $value;
        }

        return $result;
    }

    private static function intValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        throw new InvalidPluginResultException('integer field is malformed');
    }

    /** @param array<array-key, mixed> $data */
    private static function requiredField(array $data, string $key): mixed
    {
        if (! array_key_exists($key, $data)) {
            throw new InvalidPluginResultException("required field is missing: {$key}");
        }

        return $data[$key];
    }

    /** @param array<array-key, mixed> $data */
    private static function requiredBool(array $data, string $key): bool
    {
        $value = self::requiredField($data, $key);

        if (! is_bool($value)) {
            throw new InvalidPluginResultException("required boolean field is malformed: {$key}");
        }

        return $value;
    }

    private static function unsignedValue(mixed $value): int
    {
        $value = self::intValue($value);

        if ($value < 0) {
            throw new InvalidPluginResultException('unsigned integer field is malformed');
        }

        return $value;
    }

    /** @param array<array-key, mixed> $data */
    private static function optionalUnsignedField(array $data, string $key): ?int
    {
        $value = self::requiredField($data, $key);

        return $value === null ? null : self::unsignedValue($value);
    }

    /** @param array<array-key, mixed> $data */
    private static function optionalU32Field(array $data, string $key): ?int
    {
        $value = self::requiredField($data, $key);

        if ($value === null) {
            return null;
        }

        $value = self::unsignedValue($value);

        if ($value > 4_294_967_295) {
            throw new InvalidPluginResultException('u32 field is outside its range');
        }

        return $value;
    }

    private static function assertU32(?int $value): void
    {
        self::assertUnsigned($value);

        if ($value !== null && $value > 4_294_967_295) {
            throw new InvalidPluginResultException('u32 field is outside its range');
        }
    }

    private static function assertUnsigned(?int $value): void
    {
        if ($value !== null && $value < 0) {
            throw new InvalidPluginResultException('unsigned integer field is malformed');
        }
    }

    /** @return array<string, mixed> */
    private static function setting(Setting $setting): array
    {
        return ['key' => $setting->key, 'value' => $setting->value->toWire()];
    }

    /** @return array<string, mixed> */
    private static function source(Source $source): array
    {
        return ['reference' => $source->reference, 'settings' => array_map([self::class, 'setting'], $source->settings)];
    }

    /** @return array<string, mixed> */
    private static function item(Item $item): array
    {
        self::assertU32($item->durationSeconds);

        return [
            'id' => $item->id,
            'source-reference' => $item->sourceReference,
            'title' => $item->title,
            'description' => $item->description,
            'published-at' => $item->publishedAt,
            'duration-seconds' => $item->durationSeconds,
            'resources' => array_map(static function (ItemResource $resource): array {
                self::assertUnsigned($resource->sizeBytes);

                return [
                    'reference' => $resource->reference,
                    'kind' => $resource->kind,
                    'derivation-key' => $resource->derivationKey,
                    'url' => $resource->url,
                    'media-type' => $resource->mediaType,
                    'size-bytes' => $resource->sizeBytes,
                ];
            }, $item->resources),
        ];
    }
}
