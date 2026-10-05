<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * A publication reporter available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface PublicationReporter
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run report files on this publication reporter.
     * Ordinary host failures are distinct from protocol violations.
     * @param list<PublishedFile> $files
     */
    public function reportFiles(array $files): void;

    /**
     * Run report destination metadata on this publication reporter.
     * Ordinary host failures are distinct from protocol violations.
     * @param list<PluginMetadata> $metadata
     */
    public function reportDestinationMetadata(array $metadata): void;

}
