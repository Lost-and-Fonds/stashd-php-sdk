<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;

/**
 * Immutable broadcast-host.item contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Item
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param list<PreservedAsset> $assets
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        public string $id,
        public array $assets,
        public array $metadata,
    ) {}
}
