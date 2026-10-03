<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * A chunk of bytes written by the helper to stdout or stderr.
 */
final readonly class Output
{
    /**
     * Which output stream produced the bytes.
     */
    public OutputStream $stream;

    /**
     * Raw bytes exactly as written by the helper. They may contain carriage returns or non-UTF-8 data.
     */
    public string $bytes;

    /**
     * Create one helper output event. A chunk is not necessarily a complete line.
     */
    public function __construct(OutputStream $stream, string $bytes)
    {
        $this->stream = $stream;
        $this->bytes = $bytes;
    }
}
