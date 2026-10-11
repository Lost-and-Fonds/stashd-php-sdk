<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use LengthException;
use Stashd\PluginSdk\Contract\IoHost\IoHostMethod;
use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;
use Stashd\PluginSdk\Contract\IoHost\StagedWriter;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Invocation-bound host writer whose transferred identity cannot be used until returned.
 */
final class RemoteStagedWriter implements StagedWriter, OwnedResource
{
    /**
     * Create a writer for output registered with the active call.
     *
     * @param Invocation $invocation Active call that owns the writer.
     * @param string $id Host-issued writer ID.
     */
    public function __construct(
        private readonly Invocation $invocation,
        private readonly string $id,
    ) {
        $invocation->resources->requireOwned($invocation->id, $id, 'stashd:plugin/io-host.staged-writer');
    }

    /**
     * Check that only the original invocation may transfer this writer.
     */
    public function resourceId(ResourceTable $table, string $invocation, string $type): string
    {
        if ($this->invocation->resources !== $table || $this->invocation->id !== $invocation || $type !== 'stashd:plugin/io-host.staged-writer') {
            throw new ProtocolViolation('Writer proxy belongs to a different invocation or type');
        }

        $table->requireOwned($invocation, $this->id, $type);

        return $this->id;
    }

    /**
     * Append a canonical byte chunk to this unfinished output.
     */
    public function writeBytes(string $bytes): void
    {
        $this->invocation->resources->requireOwned($this->invocation->id, $this->id, 'stashd:plugin/io-host.staged-writer');
        $offset = 0;

        while ($offset < strlen($bytes)) {
            $low = 1;
            $high = min(strlen($bytes) - $offset, $this->invocation->sendMaximum());
            $accepted = 0;
            $prepared = null;
            $params = null;

            while ($low <= $high) {
                $count = intdiv($low + $high, 2);
                $chunk = array_map(ord(...), str_split(substr($bytes, $offset, $count)));

                try {
                    $candidate = (object) ['self' => ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', $this->id), 'bytes' => $chunk];
                    $prepared = $this->invocation->prepare(IoHostMethod::StagedWriterWrite, $candidate);
                    $params = $candidate;
                    $accepted = $count;
                    $low = $count + 1;
                } catch (LengthException) {
                    $high = $count - 1;
                }
            }

            if ($accepted === 0) {
                throw new LengthException('A staged byte cannot fit the peer frame maximum');
            }

            if ($params === null || $prepared === null) {
                throw new LengthException('No writable chunk');
            }
            $this->unitResult($this->invocation->call(IoHostMethod::StagedWriterWrite, $params, prepared: $prepared));
            $offset += $accepted;
        }
    }

    /**
     * Send one canonical byte chunk.
     * @param list<int> $bytes Bytes to append.
     */
    public function write(array $bytes): void
    {
        $this->invocation->resources->requireOwned($this->invocation->id, $this->id, 'stashd:plugin/io-host.staged-writer');
        $result = $this->invocation->call(IoHostMethod::StagedWriterWrite, (object) [
            'self' => ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', $this->id),
            'bytes' => array_map(static fn(int $byte): int => Values::integer('u8', $byte), $bytes),
        ]);
        $this->unitResult($result);
    }

    /**
     * Complete this writer once and receive its invocation-scoped artifact descriptor.
     */
    public function finish(): StagedArtifact
    {
        $this->invocation->resources->requireOwned($this->invocation->id, $this->id, 'stashd:plugin/io-host.staged-writer');
        $result = $this->invocation->call(IoHostMethod::StagedWriterFinish, (object) [
            'self' => ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', $this->id),
        ]);
        $value = $this->result($result);
        $artifact = $this->invocation->decode(static fn() => IoHostCodec::decodeStagedArtifact($value));

        return $artifact;
    }

    /**
     * Finish output while retaining its invocation for a later mediated reopen.
     */
    public function finishArtifact(): RemoteStagedArtifact
    {
        return new RemoteStagedArtifact($this->invocation, $this->finish());
    }

    /**
     * Explicitly discard unfinished staged output and invalidate this proxy.
     */
    public function close(): void
    {
        $this->invocation->drop($this->id, 'stashd:plugin/io-host.staged-writer');
    }

    /**
     * Require a successful unit result or raise the canonical typed staging error.
     */
    private function unitResult(mixed $result): void
    {
        if ($this->result($result) !== null) {
            $this->invocation->violate('Staged writer write must return unit');
        }
    }

    /**
     * Unwrap exactly one WIT result branch without treating a host error as a protocol failure.
     */
    private function result(mixed $result): mixed
    {
        if (!$result instanceof stdClass) {
            $this->invocation->violate('Staged writer requires a WIT result');
        }

        if (property_exists($result, 'error')) {
            $this->invocation->decode(static fn() => Values::record($result, ['error']));

            throw new HostFailure($this->invocation->decode(static fn() => IoHostCodec::decodeStagingError($result->error)));
        }

        $this->invocation->decode(static fn() => Values::record($result, ['ok']));

        return $result->ok;
    }
}
