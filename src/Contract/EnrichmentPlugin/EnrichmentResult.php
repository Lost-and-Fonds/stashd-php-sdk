<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * Immutable enrichment-plugin.enrichment-result contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class EnrichmentResult
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<PluginMetadata> $metadata
     * @param list<DerivedAsset> $assets
     */
    public function __construct(
        public array $metadata,
        public array $assets,
    ) {}
}
