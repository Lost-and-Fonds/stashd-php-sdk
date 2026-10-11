<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * RPC operations declared by the collection-export-plugin interface.
 */
enum CollectionExportPluginMethod: string
{
    /**
     * Identifies the export collection wire operation.
     */
    case ExportCollection = 'stashd:plugin/collection-export-plugin.export-collection';
}
