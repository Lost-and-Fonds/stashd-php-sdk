<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\CollectionExport\Collection;
use Stashd\PluginSdk\CollectionExport\ExportedArtifact;
use Stashd\PluginSdk\CollectionExport\Failure;
use Stashd\PluginSdk\CollectionExport\Setting;

/**
 * Build a small downloadable file from a collection and its selected options.
 */
interface CollectionExporter
{
    /**
     * Return inline bytes or a typed failure for the selected exporter.
     */
    public function export(string $exporter, Collection $collection, Setting ...$options): ExportedArtifact|Failure;
}
