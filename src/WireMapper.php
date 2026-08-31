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
            (string) ($data['reference'] ?? ''),
            self::settingsFromWire($data['settings'] ?? []),
            array_map(static fn(array $source): Source => new Source((string) ($source['reference'] ?? ''), self::settingsFromWire($source['settings'] ?? [])), self::listOfArrays($data['sources'] ?? [])),
            array_map(static fn(array $item): Item => new Item(
                (string) ($item['id'] ?? ''),
                (string) ($item['title'] ?? ''),
                array_map(static fn(array $resource): ItemResource => new ItemResource(
                    (string) ($resource['reference'] ?? ''),
                    (string) ($resource['kind'] ?? ''),
                    isset($resource['derivation-key']) ? (string) $resource['derivation-key'] : null,
                    isset($resource['url']) ? (string) $resource['url'] : null,
                    isset($resource['media-type']) ? (string) $resource['media-type'] : null,
                    (int) ($resource['size-bytes'] ?? 0),
                ), self::listOfArrays($item['resources'] ?? [])),
                isset($item['source-reference']) ? (string) $item['source-reference'] : null,
                isset($item['description']) ? (string) $item['description'] : null,
                isset($item['published-at']) ? (string) $item['published-at'] : null,
                isset($item['duration-seconds']) ? (int) $item['duration-seconds'] : null,
            ), self::listOfArrays($data['items'] ?? [])),
            $staging,
            $helpers,
        );
    }

    /** @param array<string, mixed> $data */
    public static function operationRequestFromWire(array $data): OperationRequest
    {
        return new OperationRequest((string) ($data['name'] ?? ''), self::settingsFromWire($data['settings'] ?? []), self::settingsFromWire($data['payload'] ?? []));
    }

    /** @param array<string, mixed> $data */
    public static function publicationFromWire(array $data): Publication
    {
        $artifact = is_array($data['artifact'] ?? null) ? $data['artifact'] : [];

        return new Publication(
            new Artifact((string) ($artifact['reference'] ?? ''), isset($artifact['media-type']) ? (string) $artifact['media-type'] : null, (int) ($artifact['size-bytes'] ?? 0)),
            array_map(static fn(array $file): PublishedFile => new PublishedFile((string) ($file['item-id'] ?? ''), (string) ($file['source-reference'] ?? ''), (string) ($file['relative-path'] ?? '')), self::listOfArrays($data['files'] ?? [])),
            self::settingsFromWire($data['published-metadata'] ?? []),
        );
    }

    public static function preparation(Preparation $preparation): array
    {
        return ['artifacts' => array_map(static fn(DerivedArtifact $artifact): array => [
            'item-id' => $artifact->itemId, 'reference' => $artifact->reference,
            'derived-from-reference' => $artifact->derivedFromReference, 'derivation-key' => $artifact->derivationKey,
            'kind' => $artifact->kind, 'media-type' => $artifact->mediaType, 'size-bytes' => $artifact->sizeBytes,
        ], $preparation->artifacts)];
    }

    public static function operationResult(OperationResult $result): array
    {
        return ['choices' => array_map(static fn(Choice $choice): array => ['value' => $choice->value, 'label' => $choice->label], $result->choices), 'values' => array_map([self::class, 'setting'], $result->values)];
    }

    public static function resolvedInput(ResolvedInput $input): array
    {
        return ['id' => $input->id, 'canonical-reference' => $input->canonicalReference, 'kind' => $input->kind, 'title' => $input->title, 'artwork-reference' => $input->artworkReference, 'estimated-item-count' => $input->estimatedItemCount, 'size-bytes' => $input->sizeBytes, 'size-estimated' => $input->sizeEstimated];
    }

    public static function sourceDescriptor(SourceDescriptor $source): array
    {
        return array_map(
            static fn(string $key, OptionValue $value): array => ['key' => $key, 'value' => $value->toWire()],
            array_keys($source->values),
            $source->values,
        );
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

    /** @param list<DiscoveredItem> $items */
    public static function discoveredItems(array $items): array
    {
        return array_map(static fn(DiscoveredItem $item): array => ['id' => $item->id, 'reference' => $item->reference, 'title' => $item->title, 'description' => $item->description, 'published-at' => $item->publishedAt, 'artwork-reference' => $item->artworkReference, 'duration-seconds' => $item->durationSeconds, 'kind' => $item->kind, 'size-bytes' => $item->sizeBytes, 'size-estimated' => $item->sizeEstimated], $items);
    }

    public static function acquisition(AcquisitionResult $result): array
    {
        return ['artifacts' => array_map([self::class, 'stagedArtifact'], $result->artifacts)];
    }

    /** @param array<string,mixed> $item */
    public static function discoveredItemFromWire(array $item): DiscoveredItem
    {
        return new DiscoveredItem((string) ($item['id'] ?? ''), (string) ($item['reference'] ?? ''), (string) ($item['title'] ?? ''), isset($item['description']) ? (string) $item['description'] : null, isset($item['published-at']) ? (string) $item['published-at'] : null, isset($item['artwork-reference']) ? (string) $item['artwork-reference'] : null, isset($item['duration-seconds']) ? (int) $item['duration-seconds'] : null, isset($item['kind']) ? (string) $item['kind'] : null, isset($item['size-bytes']) ? (int) $item['size-bytes'] : null, (bool) ($item['size-estimated'] ?? false));
    }

    /** @param mixed $values @return list<Setting> */
    private static function settingsFromWire(mixed $values): array
    {
        return array_map(static function (array $setting): Setting {
            $value = is_array($setting['value'] ?? null) ? $setting['value'] : [];

            return new Setting((string) ($setting['key'] ?? ''), OptionValue::fromWire($value));
        }, self::listOfArrays($values));
    }

    /** @param mixed $values @return list<array<string,mixed>> */
    private static function listOfArrays(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, static fn(mixed $value): bool => is_array($value)));
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
