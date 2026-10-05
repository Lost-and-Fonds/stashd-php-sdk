<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Contract\IoHost\ByteStream;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Host stream proxy enforcing nonempty chunks, sticky EOF and invocation ownership.
 */
final class RemoteByteStream implements ByteStream, OwnedResource
{
    /**
     * Successful EOF is terminal even if the remote producer later misbehaves.
     */
    private bool $eof = false;

    /**
     * Accept ownership of a fully validated host-created stream.
     */
    public function __construct(
        private readonly Invocation $invocation,
        private readonly string $id,
    ) {
        $invocation->resources->requireOwned($invocation->id, $id, 'stashd:plugin/io-host.byte-stream');
    }

    /**
     * Verify the stream proxy belongs to this invocation before an owned transfer.
     */
    public function resourceId(ResourceTable $table, string $invocation, string $type): string
    {
        if ($this->invocation->resources !== $table || $this->invocation->id !== $invocation || $type !== 'stashd:plugin/io-host.byte-stream') {
            throw new ProtocolViolation('Byte stream proxy belongs to another invocation or type');
        }

        $table->requireOwned($invocation, $this->id, $type);

        return $this->id;
    }

    /**
     * Read one canonical chunk; null alone indicates EOF and errors remain typed host failures.
     * @return list<int>|null
     */
    public function read(): ?array
    {
        $type = 'stashd:plugin/io-host.byte-stream';
        $this->invocation->resources->requireOwned($this->invocation->id, $this->id, $type);
        $result = $this->invocation->call('stashd:plugin/io-host.byte-stream.read', (object) [
            'self' => (object) ['$resource' => (object) ['type' => $type, 'id' => $this->id]],
        ]);

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Stream read requires a WIT result');
        }

        if (property_exists($result, 'error')) {
            Values::record($result, ['error']);

            throw new HostFailure(IoHostCodec::decodeStreamError($result->error));
        }

        Values::record($result, ['ok']);

        if ($result->ok === null) {
            $this->eof = true;

            return null;
        }

        $chunk = Values::list($result->ok);

        if ($chunk === [] || $this->eof) {
            $this->invocation->violate('Stream returned empty non-EOF chunk or bytes after EOF');
        }

        try {
            return array_map(static fn(mixed $byte): int => Values::integer('u8', $byte), $chunk);
        } catch (ProtocolViolation) {
            $this->invocation->violate('Stream chunk contains a non-byte value');
        }
    }

    /**
     * Explicitly release the stream; neither EOF nor garbage collection substitutes for release.
     */
    public function close(): void
    {
        $this->invocation->drop($this->id, 'stashd:plugin/io-host.byte-stream');
    }
}
