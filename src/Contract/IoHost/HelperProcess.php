<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A running helper, available during this call; closing it stops and reaps the child.
 * Close it when finished; it cannot be used after the call ends.
 */
interface HelperProcess
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Wait for accepted output or activity, then one terminal event, then sticky EOF.
     * Ordinary host failures are distinct from protocol violations.
     * @return HelperEvent|null
     */
    public function nextEvent(): ?HelperEvent;

    /**
     * Request cancellation; repeated requests do nothing, and an earlier outcome takes priority.
     * Ordinary host failures are distinct from protocol violations.
     * @return null
     */
    public function cancel(): null;

}
