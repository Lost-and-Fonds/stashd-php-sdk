<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\BroadcastHost\ItemCollection;
use Stashd\PluginSdk\Contract\BroadcastHost\PublicationReporter;

/**
 * A collection to publish and a reporter for the resulting files.
 */
final readonly class PublishRequest
{
    /**
     * Create the publish request.
     *
     * @param ItemCollection $collection Collection available to read for publication.
     * @param PublicationReporter $reporter Reporter used to record published files.
     * @param int $maximumReportRecordsPerBatch Upper limit on file reports per batch.
     */
    public function __construct(
        public ItemCollection $collection,
        public PublicationReporter $reporter,
        public int $maximumReportRecordsPerBatch,
    ) {}
}
