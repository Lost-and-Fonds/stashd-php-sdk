<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

use InvalidArgumentException;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helper\Stream;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Runtime\Codec\Generated\EnrichmentHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;
use Stashd\PluginSdk\Shared\Asset;
use stdClass;

/**
 * Inputs, options, and tools supplied for one selected enrichment capability.
 */
final readonly class Request
{
    /**
     * Keep selected work, caller options, and available tools together.
     * @param list<Selection> $configuration
     */
    public function __construct(
        /**
         * Saved item being enriched.
         */
        public Item $item,
        /**
         * Selected plugin-defined capability ID.
         */
        public string $capabilityId,
        /**
         * Version of the selected capability.
         */
        public string $capabilityRevision,
        /**
         * Caller-selected options. @var list<Selection>
         */
        public array $configuration,
        private Helpers $helpers,
        private Invocation $invocation,
    ) {}

    /**
     * Create temporary output or run an allowed helper.
     */

    /**
     * Return helper and staging tools.
     */
    public function helpers(): Helpers
    {
        return $this->helpers;
    }

    /**
     * Return credential selectors available for this operation.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }

    /**
     * Open one Asset granted with this item, optionally reading a byte range.
     */
    /**
     * Open a saved file at an optional byte offset and length.
     */
    public function openAsset(Asset $asset, int $offset = 0, ?int $length = null): Stream
    {
        if ($offset < 0 || ($length !== null && $length < 0)) {
            throw new InvalidArgumentException('Asset byte range cannot be negative');
        }

        $result = $this->invocation->call('stashd:plugin/enrichment-host.open-asset', (object) [
            'reference' => $asset->reference,
            'offset' => (string) $offset,
            'length' => $length === null ? null : (string) $length,
        ]);

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Asset open requires a result');
        }

        $result = Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']);

        if (property_exists($result, 'error')) {
            throw new HostFailure(EnrichmentHostCodec::decodeAssetError($result->error));
        }

        $stream = ResourceValueCodec::decode($result->ok, ['kind' => 'named', 'name' => 'byte-stream'], 'io-host', $this->invocation);

        if (!$stream instanceof RemoteByteStream) {
            $this->invocation->violate('Asset open did not return a stream');
        }

        return new Stream($stream);
    }
}
