<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A saved asset that a plugin may read during the current call.
 */
final readonly class PreservedAsset
{
    /**
     * Create a saved asset reference.
     *
     * @param string $id Stable asset ID.
     * @param string $reference Opaque read reference, not a path or URL; valid only for this call and not an access grant by itself.
     * @param string|null $mediaType Media type when known; null when unspecified.
     * @param string $sizeBytes Total bytes in the saved file.
     * @param list<PluginMetadata> $metadata Metadata facets supplied by the plugin.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $mediaType,
        public string $sizeBytes,
        public array $metadata,
    ) {}
}
