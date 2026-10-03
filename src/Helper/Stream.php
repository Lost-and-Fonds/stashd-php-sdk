<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;

/**
 * Invocation-scoped input stream whose ownership transfers when a helper starts.
 */
final class Stream
{
    /**
     * Keep the host stream private to this plugin call.
     */
    private readonly RemoteByteStream $stream;

    /**
     * Wrap a host-granted stream for helper stdin.
     */
    public function __construct(RemoteByteStream $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Supply stdin to the helper boundary for owned transfer.
     */
    public function transfer(): RemoteByteStream
    {
        return $this->stream;
    }

    /**
     * Release a stream that has not already been transferred.
     */
    public function close(): void
    {
        $this->stream->close();
    }
}
