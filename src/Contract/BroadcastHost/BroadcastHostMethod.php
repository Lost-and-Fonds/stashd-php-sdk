<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

/**
 * RPC operations declared by the broadcast-host interface.
 */
enum BroadcastHostMethod: string
{
    /**
     * Identifies the open asset wire operation.
     */
    case OpenAsset = 'stashd:plugin/broadcast-host.open-asset';
    /**
     * Identifies the item collection.next wire operation.
     */
    case ItemCollectionNext = 'stashd:plugin/broadcast-host.item-collection.next';
    /**
     * Identifies the publication reporter.report files wire operation.
     */
    case PublicationReporterReportFiles = 'stashd:plugin/broadcast-host.publication-reporter.report-files';
    /**
     * Identifies the publication reporter.report destination metadata wire operation.
     */
    case PublicationReporterReportDestinationMetadata = 'stashd:plugin/broadcast-host.publication-reporter.report-destination-metadata';
}
