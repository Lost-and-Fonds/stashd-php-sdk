<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;

/**
 * Input bytes that can be passed to a helper as stdin.
 */
final class Stream
{
    /**
     * The host-managed input stream.
     */
    private readonly RemoteByteStream $stream;

    /**
     * Create helper input from a host-provided stream.
     */
    public function __construct(RemoteByteStream $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Return the underlying stream for one-time transfer to a helper.
     */
    public function transfer(): RemoteByteStream
    {
        return $this->stream;
    }

    /**
     * Close the input if it has not already been handed to a helper.
     */
    public function close(): void
    {
        $this->stream->close();
    }
}
