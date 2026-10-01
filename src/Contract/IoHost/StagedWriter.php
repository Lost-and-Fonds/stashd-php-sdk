<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Invocation-scoped io-host.staged-writer capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface StagedWriter
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical staged-writer.write on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @param list<int> $bytes
     */
    public function write(array $bytes): void;

    /**
     * Invoke canonical staged-writer.finish on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @return StagedArtifact
     */
    public function finish(): StagedArtifact;

}
