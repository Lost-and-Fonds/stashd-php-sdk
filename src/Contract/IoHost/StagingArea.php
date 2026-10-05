<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A staging area available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface StagingArea
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run create on this staging area.
     * Ordinary host failures are distinct from protocol violations.
     * @param string|null $mediaType
     * @param list<PluginMetadata> $metadata
     * @return StagedWriter
     */
    public function create(?string $mediaType, array $metadata): StagedWriter;

}
