<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Invocation-scoped io-host.staging-area capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface StagingArea
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical staging-area.create on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @param string|null $mediaType
     * @param list<PluginMetadata> $metadata
     * @return StagedWriter
     */
    public function create(?string $mediaType, array $metadata): StagedWriter;

}
