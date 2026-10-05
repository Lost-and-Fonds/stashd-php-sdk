<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Generator;
use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;

/**
 * Input bytes that can be passed to a helper as stdin.
 */
final class Stream
{
    /**
     * Create helper input from a supplied stream.
     *
     * @param RemoteByteStream $stream Readable bytes available during this call.
     */
    public function __construct(private readonly RemoteByteStream $stream) {}

    /**
     * Return the underlying stream for one-time transfer to a helper.
     */
    public function transfer(): RemoteByteStream
    {
        return $this->stream;
    }

    /**
     * Read a stream chunk at a time and close it when iteration finishes.
     * @return Generator<int, string>
     */
    public function chunks(): Generator
    {
        try {
            while (($chunk = $this->stream->read()) !== null) {
                yield pack('C*', ...$chunk);
            }
        } finally {
            $this->close();
        }
    }

    /**
     * Close the input if it has not already been handed to a helper.
     */
    public function close(): void
    {
        $this->stream->close();
    }
}
