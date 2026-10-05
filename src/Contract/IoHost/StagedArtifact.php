<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A receipt for completed output; its fields must match the saved receipt.
 */
final readonly class StagedArtifact
{
    /**
     * Create the staged artifact.
     *
     * @param string $reference Opaque reference interpreted by the service that issued it.
     * @param string|null $mediaType Media type when known; null when unspecified.
     * @param string $sizeBytes Total bytes written to this staged output.
     * @param list<PluginMetadata> $metadata Metadata facets supplied by the plugin.
     */
    public function __construct(
        public string $reference,
        public ?string $mediaType,
        public string $sizeBytes,
        public array $metadata,
    ) {}
}
