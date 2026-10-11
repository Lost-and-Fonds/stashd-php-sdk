<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use Generator;
use InvalidArgumentException;
use LengthException;
use Stashd\PluginSdk\Contract\BroadcastHost\BroadcastHostMethod;
use Stashd\PluginSdk\Contract\BroadcastHost\CollectionReadError;
use Stashd\PluginSdk\Contract\IoHost\StreamError;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helper\Stream;
use Stashd\PluginSdk\Runtime\Codec\AuthorValues;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\OwnedResource;
use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;
use Stashd\PluginSdk\Shared\Asset;
use Stashd\PluginSdk\Shared\Metadata;
use Stashd\PluginSdk\Tools;
use stdClass;

/**
 * Read a selected collection in batches and report one completed publication.
 */
final class Publish
{
    /**
     * Whether all selected items have been read.
     */
    private bool $eof = false;
    /**
     * A collection failure prevents publishing a successful prefix.
     */
    private bool $readFailed = false;

    /**
     * Require complete enumeration before reporting lifecycle success.
     */
    public function requireComplete(): void
    {
        if (!$this->eof || $this->readFailed) {
            $this->invocation->violate('Publication succeeded before complete collection enumeration');
        }
    }

    /**
     * Create a publication request with its collection and file reporter.
     *
     * @param Invocation $invocation Active call used to read items and report files.
     * @param OwnedResource $collection Selected collection supplied by the host.
     * @param OwnedResource $reporter Reporter for the files produced by publication.
     * @param int $maximumReportRecordsPerBatch Positive upper limit on file reports per batch.
     * @param list<Setting> $settings Caller-selected destination settings.
     * @param Tools $tools Tools and credentials available during this call.
     */
    public function __construct(
        private readonly Invocation $invocation,
        private readonly OwnedResource $collection,
        private readonly OwnedResource $reporter,
        private readonly int $maximumReportRecordsPerBatch,
        public readonly array $settings,
        private readonly Tools $tools,
    ) {
        if ($maximumReportRecordsPerBatch < 1) {
            throw new ProtocolViolation('Publication report maximum must be positive');
        }
    }

    /**
     * Read selected items in batches. Their order does not imply a sort order.
     * @return Generator<int, Item>
     */
    public function items(int $batchSize = 32): Generator
    {
        $batchSize = min($batchSize, 4294967295);

        if ($batchSize < 1) {
            throw new InvalidArgumentException('Batch size must be positive');
        }

        while (!$this->eof) {
            $result = $this->invocation->typedCall(
                BroadcastHostMethod::ItemCollectionNext,
                [
                    'self' => $this->collection,
                    'max-items' => $batchSize,
                ],
                [
                    'self' => [
                        'kind' => 'borrow',
                        'value' => [
                            'kind' => 'named',
                            'name' => 'item-collection',
                        ],
                    ],
                    'max-items' => [
                        'kind' => 'scalar',
                        'name' => 'u32',
                    ],
                ],
                [
                    'kind' => 'result',
                    'ok' => [
                        'kind' => 'option',
                        'value' => [
                            'kind' => 'list',
                            'value' => [
                                'kind' => 'named',
                                'name' => 'item',
                            ],
                        ],
                    ],
                    'error' => [
                        'kind' => 'named',
                        'name' => 'collection-read-error',
                    ],
                ],
                'broadcast-host',
            );

            if (!$result instanceof stdClass) {
                $this->invocation->violate('Collection read requires a result');
            }

            $result = $this->invocation->decode(static fn() => Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']));

            if (property_exists($result, 'error')) {
                if (!$result->error instanceof CollectionReadError) {
                    $this->invocation->violate('Invalid collection error');
                }
                $this->readFailed = true;

                throw new HostFailure($result->error);
            }

            if ($result->ok === null) {
                $this->eof = true;

                return;
            }

            if (!is_array($result->ok) || !array_is_list($result->ok) || $result->ok === [] || count($result->ok) > $batchSize) {
                $this->invocation->violate('Collection batch must be an ordered list');
            }

            foreach ($result->ok as $item) {
                if (!$item instanceof \Stashd\PluginSdk\Contract\BroadcastHost\Item) {
                    $this->invocation->violate('Invalid decoded collection item');
                }
                yield $this->invocation->decode(static fn() => AuthorValues::broadcastItem($item));
            }
        }
    }

    /**
     * Read a preserved asset granted by enumerating its item.
     */
    public function openAsset(Asset $asset, int $offset = 0, ?int $length = null): Stream
    {
        if ($offset < 0 || ($length !== null && $length < 0)) {
            throw new InvalidArgumentException('Asset byte range cannot be negative');
        }
        $result = $this->invocation->typedCall(BroadcastHostMethod::OpenAsset, ['reference' => $asset->reference, 'offset' => (string) $offset, 'length' => $length === null ? null : (string) $length], ['reference' => ['kind' => 'scalar', 'name' => 'string'], 'offset' => ['kind' => 'scalar', 'name' => 'u64'], 'length' => ['kind' => 'option', 'value' => ['kind' => 'scalar', 'name' => 'u64']]], ['kind' => 'result', 'ok' => ['kind' => 'named', 'name' => 'byte-stream'], 'error' => ['kind' => 'named', 'name' => 'stream-error']], 'broadcast-host');

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Asset open requires a result');
        }

        if (property_exists($result, 'error')) {
            if (!$result->error instanceof StreamError) {
                $this->invocation->violate('Invalid asset stream error');
            }

            throw new HostFailure($result->error);
        }

        if (!$result->ok instanceof RemoteByteStream) {
            $this->invocation->violate('Asset open did not return a stream');
        }

        return new Stream($result->ok);
    }

    /**
     * Report destination files in bounded batches.
     * @param list<PublishedFile> $files Files published by this invocation.
     */
    public function reportFiles(array $files): void
    {
        $this->report(BroadcastHostMethod::PublicationReporterReportFiles, 'files', array_map(
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
        $this->report(BroadcastHostMethod::PublicationReporterReportDestinationMetadata, 'metadata', array_map(AuthorValues::encodeMetadata(...), $metadata));
    }

    /**
     * Return staging and helper tools for this publication.
     */

    /**
     * Return staging and helper tools for this publication.
     */
    public function tools(): Tools
    {
        return $this->tools;
    }

    /**
     * Return credential selectors supplied for this publication.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->tools->credentials;
    }

    /**
     * Check a provisional report's typed result before returning.
     * @param list<stdClass> $values
     */
    private function report(BroadcastHostMethod $method, string $field, array $values): void
    {
        while ($values !== []) {
            $count = min(count($values), $this->maximumReportRecordsPerBatch);

            while (true) {
                try {
                    $prepared = $this->invocation->prepare($method, (object) ['self' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.publication-reporter', $this->reporter->resourceId($this->invocation->resources, $this->invocation->id, 'stashd:plugin/broadcast-host.publication-reporter')), $field => array_slice($values, 0, $count)]);

                    break;
                } catch (LengthException $error) {
                    if ($count === 1) {
                        throw $error;
                    }
                    $count = max(1, intdiv($count, 2));
                }
            }
            $this->reportBatch($method, $field, array_splice($values, 0, $count), $prepared);
        }
    }

    /**
     * Send one frame-sized provisional report batch.
     * @param list<stdClass> $values Ordered report records.
     * @param array{string, string} $prepared Exact accepted payload and ID.
     */
    private function reportBatch(BroadcastHostMethod $method, string $field, array $values, array $prepared): void
    {
        $result = $this->invocation->call($method, (object) [
            'self' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.publication-reporter', $this->reporter->resourceId($this->invocation->resources, $this->invocation->id, 'stashd:plugin/broadcast-host.publication-reporter')),
            $field => $values,
        ], prepared: $prepared);

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Publication report requires a result');
        }

        $result = $this->invocation->decode(static fn() => Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']));

        if (property_exists($result, 'error')) {
            throw new HostFailure($this->invocation->decode(static fn() => BroadcastHostCodec::decodePublicationReportError($result->error)));
        }

        if ($result->ok !== null) {
            $this->invocation->violate('Publication report must return unit');
        }
    }
}
