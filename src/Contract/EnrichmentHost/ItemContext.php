<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;

/**
 * Immutable enrichment-host.item-context contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class ItemContext
{
    /**
     * Canonical item-id value; retained in contract order without normalization.
     * @var string
     */
    public string $itemId;

    /**
     * Canonical assets value; retained in contract order without normalization.
     * @var list<PreservedAsset>
     */
    public array $assets;

    /**
     * Canonical metadata value; retained in contract order without normalization.
     * @var list<PluginMetadata>
     */
    public array $metadata;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $itemId
     * @param list<PreservedAsset> $assets
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        string $itemId,
        array $assets,
        array $metadata,
    ) {
        $this->itemId = $itemId;
        $this->assets = $assets;
        $this->metadata = $metadata;
    }
}
