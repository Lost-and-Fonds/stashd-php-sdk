<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Internal collection-export-plugin methods used by the runtime.
 */
interface Plugin
{
    /**
     * Run export collection with the supplied values.
     * @param string $exporter
     * @param Collection $collection
     * @param list<Setting> $options
     * @return ExportedArtifact|PluginError
     */
    public function exportCollection(string $exporter, Collection $collection, array $options): ExportedArtifact|PluginError;

}
