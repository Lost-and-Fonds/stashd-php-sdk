<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Items to save together with a discovery checkpoint.
 */
final readonly class DiscoveryBatch
{
    /**
     * Create the discovery batch.
     *
     * @param list<DiscoveredItem> $items Items included in this batch, in order.
     * @param DiscoveryProgress $progress Checkpoint or completion result saved together with these items.
     */
    public function __construct(
        public array $items,
        public DiscoveryProgress $progress,
    ) {}
}
