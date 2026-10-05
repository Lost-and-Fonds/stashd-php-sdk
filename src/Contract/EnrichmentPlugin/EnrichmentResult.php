<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * Metadata and assets produced by enrichment.
 */
final readonly class EnrichmentResult
{
    /**
     * Create the enrichment result.
     *
     * @param list<PluginMetadata> $metadata Metadata facets supplied by the plugin.
     * @param list<DerivedAsset> $assets Assets associated with this result.
     */
    public function __construct(
        public array $metadata,
        public array $assets,
    ) {}
}
