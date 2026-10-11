<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Generator;
use LogicException;
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
    public function __construct(private ?RemoteByteStream $stream) {}

    /**
     * Return the underlying stream for one-time transfer to a helper.
     */
    public function transfer(): RemoteByteStream
    {
        return $this->stream ?? throw new LogicException('Stream was handed to a helper');
    }

    /**
     * Read a stream chunk at a time and close it when iteration finishes.
     */
    public function consume(): void
    {
        $this->stream = null;
    }

    /**
     * Read bytes while this wrapper still owns the stream.
     * @return Generator<int, string>
     */
    public function chunks(): Generator
    {
        try {
            while (($chunk = $this->transfer()->read()) !== null) {
                yield pack('C*', ...$chunk);
            }
        } finally {
            if ($this->stream !== null) {
                $this->close();
            }
        }
    }

    /**
     * Close the input if it has not already been handed to a helper.
     */
    public function close(): void
    {
        $this->transfer()->close();
    }
}
