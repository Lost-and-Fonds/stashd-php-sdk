<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Invocation-scoped live host process; drop terminates and reaps a running child.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface HelperProcess
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Wait for accepted output or activity, then one terminal event, then sticky EOF.
     * Ordinary host failures are distinct from protocol violations.
     * @return HelperEvent|null
     */
    public function nextEvent(): ?HelperEvent;

    /**
     * Request idempotent cancellation; the first determined terminal condition wins.
     * Ordinary host failures are distinct from protocol violations.
     * @return null
     */
    public function cancel(): null;

}
