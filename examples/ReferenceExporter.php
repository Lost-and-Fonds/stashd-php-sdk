<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Examples;

use Stashd\PluginSdk\CollectionExport\Collection;
use Stashd\PluginSdk\CollectionExport\ErrorKind;
use Stashd\PluginSdk\CollectionExport\ExportedArtifact;
use Stashd\PluginSdk\CollectionExport\Failure;
use Stashd\PluginSdk\CollectionExport\Setting;
use Stashd\PluginSdk\CollectionExporter;

/**
 * Small domain-neutral example exporting opaque references without assuming they are URLs.
 */
final class ReferenceExporter implements CollectionExporter
{
    /**
     * Return a length-prefixed reference list so embedded newlines remain unambiguous.
     */
    public function export(string $exporter, Collection $collection, Setting ...$options): ExportedArtifact|Failure
    {
        if ($exporter !== 'references') {
            return new Failure(ErrorKind::Unsupported, 'Unknown exporter');
        }

        $contents = '';

        foreach ($collection->entries as $entry) {
            $contents .= strlen($entry->reference) . ':' . $entry->reference . "\n";
        }

        return new ExportedArtifact('references.txt', 'text/plain', $contents);
    }
}
