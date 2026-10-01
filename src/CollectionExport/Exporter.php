<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Author-facing bounded interchange lifecycle without HTTP, credentials, staging or progress capabilities.
 */
interface Exporter
{
    /**
     * Produce inline interchange bytes or an ordinary typed failure for the requested opaque exporter ID.
     */
    public function export(string $exporter, Collection $collection, Setting ...$options): ExportedArtifact|Failure;
}
