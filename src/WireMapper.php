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

        return ['reference' => $artifact->reference, 'media-type' => $artifact->mediaType, 'size-bytes' => $artifact->sizeBytes, 'role' => $artifact->role];
    }

    /** @param array<string, mixed> $data */
    public static function publishRequestFromWire(array $data, ?StagingArea $staging = null, ?HelperRunner $helpers = null): PublishRequest
    {
        return new PublishRequest(
            self::stringValue($data['reference'] ?? null),
            self::settingsFromWire($data['settings'] ?? []),
            array_map(static fn(array $source): Source => new Source(self::stringValue($source['reference'] ?? null), self::settingsFromWire($source['settings'] ?? [])), self::listOfArrays($data['sources'] ?? [])),
            array_map(static fn(array $item): Item => new Item(
                self::stringValue($item['id'] ?? null),
                self::stringValue($item['title'] ?? null),
                array_map(static fn(array $resource): ItemResource => new ItemResource(
                    self::stringValue($resource['reference'] ?? null),
                    self::stringValue($resource['kind'] ?? null),
                    isset($resource['derivation-key']) ? self::stringValue($resource['derivation-key']) : null,
                    isset($resource['url']) ? self::stringValue($resource['url']) : null,
                    isset($resource['media-type']) ? self::stringValue($resource['media-type']) : null,
                    self::intValue($resource['size-bytes'] ?? null),
                ), self::listOfArrays($item['resources'] ?? [])),
                isset($item['source-reference']) ? self::stringValue($item['source-reference']) : null,
                isset($item['description']) ? self::stringValue($item['description']) : null,
                isset($item['published-at']) ? self::stringValue($item['published-at']) : null,
                isset($item['duration-seconds']) ? self::intValue($item['duration-seconds']) : null,
            ), self::listOfArrays($data['items'] ?? [])),
            $staging,
            $helpers,
        );
    }

    /** @param array<string, mixed> $data */
    public static function operationRequestFromWire(array $data): OperationRequest
    {
        return new OperationRequest(self::stringValue($data['name'] ?? null), self::settingsFromWire($data['settings'] ?? []), self::settingsFromWire($data['payload'] ?? []));
    }

    /** @param array<string, mixed> $data */
    public static function publicationFromWire(array $data): Publication
    {
        $artifact = is_array($data['artifact'] ?? null) ? $data['artifact'] : [];

        return new Publication(
            new Artifact(self::stringValue($artifact['reference'] ?? null), isset($artifact['media-type']) ? self::stringValue($artifact['media-type']) : null, self::intValue($artifact['size-bytes'] ?? null)),
            array_map(static fn(array $file): PublishedFile => new PublishedFile(self::stringValue($file['item-id'] ?? null), self::stringValue($file['source-reference'] ?? null), self::stringValue($file['relative-path'] ?? null)), self::listOfArrays($data['files'] ?? [])),
            self::settingsFromWire($data['published-metadata'] ?? []),
        );
    }

    /** @return array<string,mixed> */
    public static function preparation(Preparation $preparation): array
    {
        return ['artifacts' => array_map(static fn(DerivedArtifact $artifact): array => [
            'item-id' => $artifact->itemId, 'reference' => $artifact->reference,
            'derived-from-reference' => $artifact->derivedFromReference, 'derivation-key' => $artifact->derivationKey,
            'kind' => $artifact->kind, 'media-type' => $artifact->mediaType, 'size-bytes' => $artifact->sizeBytes,
        ], $preparation->artifacts)];
    }

    /** @return array<string,mixed> */
    public static function operationResult(OperationResult $result): array
    {
        return ['choices' => array_map(static fn(Choice $choice): array => ['value' => $choice->value, 'label' => $choice->label], $result->choices), 'values' => array_map([self::class, 'setting'], $result->values)];
    }

    /** @return array<string,mixed> */
    public static function resolvedInput(ResolvedInput $input): array
    {
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
            $key = $value['key'] ?? null;
            $encoded = $value['value'] ?? null;

            if (is_string($key) && is_array($encoded)) {
                $source[$key] = OptionValue::fromWire($encoded);
            }
        }

        return new SourceDescriptor($source);
    }

    /**
     * @param list<DiscoveredItem> $items
     * @return list<array<string, mixed>>
     */
    public static function discoveredItems(array $items): array
    {
        return array_map(static fn(DiscoveredItem $item): array => ['id' => $item->id, 'reference' => $item->reference, 'title' => $item->title, 'description' => $item->description, 'published-at' => $item->publishedAt, 'artwork-reference' => $item->artworkReference, 'duration-seconds' => $item->durationSeconds, 'kind' => $item->kind, 'size-bytes' => $item->sizeBytes, 'size-estimated' => $item->sizeEstimated], $items);
    }

    /** @return array<string,mixed> */
    public static function acquisition(AcquisitionResult $result): array
    {
        return ['artifacts' => array_map([self::class, 'stagedArtifact'], $result->artifacts)];
    }

    /** @param array<string, mixed> $item */
    public static function discoveredItemFromWire(array $item): DiscoveredItem
    {
        return new DiscoveredItem(self::stringValue($item['id'] ?? null), self::stringValue($item['reference'] ?? null), self::stringValue($item['title'] ?? null), isset($item['description']) ? self::stringValue($item['description']) : null, isset($item['published-at']) ? self::stringValue($item['published-at']) : null, isset($item['artwork-reference']) ? self::stringValue($item['artwork-reference']) : null, isset($item['duration-seconds']) ? self::intValue($item['duration-seconds']) : null, isset($item['kind']) ? self::stringValue($item['kind']) : null, isset($item['size-bytes']) ? self::intValue($item['size-bytes']) : null, is_bool($item['size-estimated'] ?? null) ? $item['size-estimated'] : false);
    }

    /**
     * @param mixed $values
     * @return list<Setting>
     */
    private static function settingsFromWire(mixed $values): array
    {
        $result = [];

        foreach (self::listOfArrays($values) as $setting) {
            $value = is_array($setting['value'] ?? null) ? $setting['value'] : [];
            $result[] = new Setting(self::stringValue($setting['key'] ?? null), OptionValue::fromWire($value));
        }

        return $result;
    }

    /**
     * @param mixed $values
     * @return list<array<string, mixed>>
     */
    private static function listOfArrays(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $result = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                /** @var array<string,mixed> $value */
                $result[] = $value;
            }
        }

        return $result;
    }

    private static function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function intValue(mixed $value): int
    {
        return is_int($value) || is_float($value) || is_string($value) ? (int) $value : 0;
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
        return [
            'id' => $item->id,
            'source-reference' => $item->sourceReference,
            'title' => $item->title,
            'description' => $item->description,
            'published-at' => $item->publishedAt,
            'duration-seconds' => $item->durationSeconds,
            'resources' => array_map(static fn(ItemResource $resource): array => [
                'reference' => $resource->reference, 'kind' => $resource->kind, 'derivation-key' => $resource->derivationKey,
                'url' => $resource->url, 'media-type' => $resource->mediaType, 'size-bytes' => $resource->sizeBytes,
            ], $item->resources),
        ];
    }
}
