<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Finished staged output that may be reopened during its originating invocation.
 */
final class RemoteStagedArtifact
{
    /**
     * Create readable output from a completed receipt.
     *
     * @param Invocation $invocation Active call in which the output was created.
     * @param StagedArtifact $receipt Receipt returned when the writer finished.
     */
    public function __construct(
        private readonly Invocation $invocation,
        private readonly StagedArtifact $receipt,
    ) {}

    /**
     * Return the finished descriptor for an outbound lifecycle result.
     */
    public function receipt(): StagedArtifact
    {
        $this->invocation->requireActive();

        return $this->receipt;
    }

    /**
     * Open a reader over all bytes of the finished staged output.
     */
    public function open(): RemoteByteStream
    {
        $result = $this->invocation->call('stashd:plugin/io-host.open-staged-artifact', (object) [
            'artifact' => IoHostCodec::encodeStagedArtifact($this->receipt), 'offset' => '0', 'length' => null,
        ]);

        try {
            if (!$result instanceof stdClass) {
                throw new ProtocolViolation('Opening staged output requires a WIT result');
            }

            if (property_exists($result, 'error')) {
                Values::record($result, ['error']);

                throw new HostFailure(IoHostCodec::decodeStreamError($result->error));
            }

            Values::record($result, ['ok']);
            $stream = ResourceValueCodec::decode($result->ok, ['kind' => 'named', 'name' => 'byte-stream'], 'io-host', $this->invocation);

            if (!$stream instanceof RemoteByteStream) {
                throw new ProtocolViolation('Opening staged output did not return a stream');
            }

            return $stream;
        } catch (ProtocolViolation $error) {
            $this->invocation->violate($error->getMessage());
        }
    }
}
