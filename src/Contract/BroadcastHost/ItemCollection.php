<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

/**
 * Invocation-scoped broadcast-host.item-collection capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface ItemCollection
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical item-collection.next on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @param int $maxItems
     * @return list<Item>|null
     */
    public function next(int $maxItems): ?array;

}
