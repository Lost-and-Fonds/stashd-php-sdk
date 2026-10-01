<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Typed author lifecycle surface for collection-export-plugin.
 * Values are independent of JSON framing and host process reuse.
 */
interface Plugin
{
    /**
     * Execute canonical export-collection using current invocation values only.
     * @param string $exporter
     * @param Collection $collection
     * @param list<Setting> $options
     * @return ExportedArtifact|PluginError
     */
    public function exportCollection(string $exporter, Collection $collection, array $options): ExportedArtifact|PluginError;

}
