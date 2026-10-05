<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use Generator;
use InvalidArgumentException;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Runtime\Codec\AuthorValues;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\OwnedResource;
use Stashd\PluginSdk\Shared\Metadata;
use stdClass;

/**
 * Read a selected collection in batches and report one completed publication.
 */
final class Publish
{
    private bool $eof = false;

    /**
     * Keep the selected collection and reporter bound to this publication.
     * @param list<Setting> $settings
     */
    public function __construct(
        private readonly Invocation $invocation,
        private readonly OwnedResource $collection,
        private readonly OwnedResource $reporter,
        private readonly int $maximumReportRecordsPerBatch,
        public readonly array $settings,
        private readonly Helpers $helpers,
    ) {
        if ($maximumReportRecordsPerBatch < 1) {
            throw new ProtocolViolation('Publication report maximum must be positive');
        }
    }

    /**
     * Yield selected items in bounded batches. Order is not a domain ordering.
     * @return Generator<int, Item>
     */
    public function items(int $batchSize = 32): Generator
    {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('Batch size must be positive');
        }

        while (!$this->eof) {
            $result = $this->invocation->typedCall('stashd:plugin/broadcast-host.item-collection.next', [
                'self' => $this->collection, 'max-items' => $batchSize,
            ], [
                'self' => ['kind' => 'borrow', 'value' => ['kind' => 'named', 'name' => 'item-collection']],
                'max-items' => ['kind' => 'scalar', 'name' => 'u32'],
            ], ['kind' => 'result', 'ok' => ['kind' => 'option', 'value' => ['kind' => 'list', 'value' => ['kind' => 'named', 'name' => 'item']]], 'error' => ['kind' => 'named', 'name' => 'collection-read-error']], 'broadcast-host');

            if (!$result instanceof stdClass) {
                $this->invocation->violate('Collection read requires a result');
            }

            $result = Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']);

            if (property_exists($result, 'error')) {
                throw new HostFailure(BroadcastHostCodec::decodeCollectionReadError($result->error));
            }

            if ($result->ok === null) {
                $this->eof = true;

                return;
            }

            if (!is_array($result->ok) || !array_is_list($result->ok)) {
                $this->invocation->violate('Collection batch must be an ordered list');
            }

            foreach ($result->ok as $item) {
                yield AuthorValues::broadcastItem($item);
            }
        }
    }

    /**
     * Report files produced for this publication.
     * @param list<PublishedFile> $files
     */
    public function reportFiles(array $files): void
    {
        $this->report('stashd:plugin/broadcast-host.publication-reporter.report-files', 'files', array_map(
            static fn(PublishedFile $file) => BroadcastHostCodec::encodePublishedFile(new \Stashd\PluginSdk\Contract\BroadcastHost\PublishedFile($file->itemId, $file->assetId, $file->relativePath)),
            $files,
        ));
    }

    /**
     * Report destination-level plugin metadata separately from file reports.
     * @param list<Metadata> $metadata
     */
    public function reportDestinationMetadata(array $metadata): void
    {
        $this->report('stashd:plugin/broadcast-host.publication-reporter.report-destination-metadata', 'metadata', array_map(AuthorValues::encodeMetadata(...), $metadata));
    }

    /**
     * Return staging and helper tools for this publication.
     */

    /**
     * Return staging and helper tools for this publication.
     */
    public function helpers(): Helpers
    {
        return $this->helpers;
    }

    /**
     * Return credential selectors supplied for this publication.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }

    /**
     * Check a provisional report's typed result before returning.
     * @param list<stdClass> $values
     */
    private function report(string $method, string $field, array $values): void
    {
        $result = $this->invocation->call($method, (object) [
            'self' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.publication-reporter', $this->reporter->resourceId($this->invocation->resources, $this->invocation->id, 'stashd:plugin/broadcast-host.publication-reporter')),
            $field => $values,
        ]);

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Publication report requires a result');
        }

        $result = Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']);

        if (property_exists($result, 'error')) {
            throw new HostFailure(BroadcastHostCodec::decodePublicationReportError($result->error));
        }

        if ($result->ok !== null) {
            $this->invocation->violate('Publication report must return unit');
        }
    }
}
