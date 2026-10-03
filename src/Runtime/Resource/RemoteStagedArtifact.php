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
     * Invocation in which this finished output is valid.
     */
    private readonly Invocation $invocation;

    /**
     * Host-issued descriptor checked when reopening the output.
     */
    private readonly StagedArtifact $receipt;

    /**
     * Keep the host-issued receipt and its invocation together.
     */
    public function __construct(Invocation $invocation, StagedArtifact $receipt)
    {
        $this->invocation = $invocation;
        $this->receipt = $receipt;
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
