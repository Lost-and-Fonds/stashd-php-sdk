<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

use InvalidArgumentException;
use Stashd\PluginSdk\Contract\EnrichmentHost\EnrichmentHostMethod;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helper\Stream;
use Stashd\PluginSdk\Runtime\Codec\Generated\EnrichmentHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;
use Stashd\PluginSdk\Shared\Asset;
use Stashd\PluginSdk\Tools;
use stdClass;

/**
 * Inputs, options, and tools supplied for one selected enrichment capability.
 */
final readonly class Request
{
    /**
     * Create a request to enrich the selected item.
     *
     * @param Item $item Saved item being enriched.
     * @param string $capabilityId Selected plugin-defined capability ID.
     * @param string $capabilityRevision Version of the selected capability.
     * @param list<Selection> $configuration Caller-selected options.
     * @param Tools $tools Tools and credentials available during this call.
     * @param Invocation $invocation Active call used to read the supplied assets.
     */
    public function __construct(
        public Item $item,
        public string $capabilityId,
        public string $capabilityRevision,
        public array $configuration,
        private Tools $tools,
        private Invocation $invocation,
    ) {}

    /**
     * Return credential selectors available for this operation.
     */
    public function tools(): Tools
    {
        return $this->tools;
    }

    /**
     * Return credentials available during this call.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->tools->credentials;
    }

    /**
     * Open a saved file at an optional byte offset and length.
     */
    public function openAsset(Asset $asset, int $offset = 0, ?int $length = null): Stream
    {
        if ($offset < 0 || ($length !== null && $length < 0)) {
            throw new InvalidArgumentException('Asset byte range cannot be negative');
        }

        $result = $this->invocation->call(EnrichmentHostMethod::OpenAsset, (object) [
            'reference' => $asset->reference,
            'offset' => (string) $offset,
            'length' => $length === null ? null : (string) $length,
        ]);

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Asset open requires a result');
        }

        $result = $this->invocation->decode(static fn() => Values::record($result, property_exists($result, 'error') ? ['error'] : ['ok']));

        if (property_exists($result, 'error')) {
            throw new HostFailure($this->invocation->decode(static fn() => EnrichmentHostCodec::decodeAssetError($result->error)));
        }

        $stream = $this->invocation->decode(fn() => ResourceValueCodec::decode($result->ok, ['kind' => 'named', 'name' => 'byte-stream'], 'io-host', $this->invocation));

        if (!$stream instanceof RemoteByteStream) {
            $this->invocation->violate('Asset open did not return a stream');
        }

        return new Stream($stream);
    }
}
