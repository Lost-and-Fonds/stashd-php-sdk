<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

/**
 * A item collection available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface ItemCollection
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run next on this item collection.
     * Ordinary host failures are distinct from protocol violations.
     * @param int $maxItems
     * @return list<Item>|null
     */
    public function next(int $maxItems): ?array;

}
