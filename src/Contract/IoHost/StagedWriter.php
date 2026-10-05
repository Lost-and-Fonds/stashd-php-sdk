<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A staged writer available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface StagedWriter
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run write on this staged writer.
     * Ordinary host failures are distinct from protocol violations.
     * @param list<int> $bytes
     */
    public function write(array $bytes): void;

    /**
     * Run finish on this staged writer.
     * Ordinary host failures are distinct from protocol violations.
     * @return StagedArtifact
     */
    public function finish(): StagedArtifact;

}
