<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\BroadcastHost\ItemCollection;
use Stashd\PluginSdk\Contract\BroadcastHost\PublicationReporter;

/**
 * Immutable broadcast-plugin.publish-request contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class PublishRequest
{
    /**
     * Canonical collection value; retained in contract order without normalization.
     * @var ItemCollection
     */
    public ItemCollection $collection;

    /**
     * Canonical reporter value; retained in contract order without normalization.
     * @var PublicationReporter
     */
    public PublicationReporter $reporter;

    /**
     * Canonical maximum-report-records-per-batch value; retained in contract order without normalization.
     * @var int
     */
    public int $maximumReportRecordsPerBatch;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param ItemCollection $collection
     * @param PublicationReporter $reporter
     * @param int $maximumReportRecordsPerBatch
     */
    public function __construct(
        ItemCollection $collection,
        PublicationReporter $reporter,
        int $maximumReportRecordsPerBatch,
    ) {
        $this->collection = $collection;
        $this->reporter = $reporter;
        $this->maximumReportRecordsPerBatch = $maximumReportRecordsPerBatch;
    }
}
