<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Invocation-scoped io-host.byte-stream capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface ByteStream
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical byte-stream.read on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @return list<int>|null
     */
    public function read(): ?array;

}
