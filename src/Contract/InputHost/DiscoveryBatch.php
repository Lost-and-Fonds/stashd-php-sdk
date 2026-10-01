<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Immutable input-host.discovery-batch contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class DiscoveryBatch
{
    /**
     * Canonical items value; retained in contract order without normalization.
     * @var list<DiscoveredItem>
     */
    public array $items;

    /**
     * Canonical progress value; retained in contract order without normalization.
     * @var DiscoveryProgress
     */
    public DiscoveryProgress $progress;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<DiscoveredItem> $items
     * @param DiscoveryProgress $progress
     */
    public function __construct(
        array $items,
        DiscoveryProgress $progress,
    ) {
        $this->items = $items;
        $this->progress = $progress;
    }
}
