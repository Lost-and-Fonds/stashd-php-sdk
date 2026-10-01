<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * Immutable enrichment-plugin.enrichment-result contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class EnrichmentResult
{
    /**
     * Canonical metadata value; retained in contract order without normalization.
     * @var list<PluginMetadata>
     */
    public array $metadata;

    /**
     * Canonical assets value; retained in contract order without normalization.
     * @var list<DerivedAsset>
     */
    public array $assets;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<PluginMetadata> $metadata
     * @param list<DerivedAsset> $assets
     */
    public function __construct(
        array $metadata,
        array $assets,
    ) {
        $this->metadata = $metadata;
        $this->assets = $assets;
    }
}
