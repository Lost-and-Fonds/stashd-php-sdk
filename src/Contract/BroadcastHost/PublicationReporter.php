<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * Invocation-scoped broadcast-host.publication-reporter capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface PublicationReporter
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical publication-reporter.report-files on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @param list<PublishedFile> $files
     */
    public function reportFiles(array $files): void;

    /**
     * Invoke canonical publication-reporter.report-destination-metadata on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @param list<PluginMetadata> $metadata
     */
    public function reportDestinationMetadata(array $metadata): void;

}
