<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * One nonempty raw byte chunk from a helper's stdout or stderr pipe.
 */
final readonly class Output
{
    /**
     * Source pipe for the bytes.
     */
    public OutputStream $stream;

    /**
     * Unmodified arbitrary bytes, including carriage returns and invalid UTF-8.
     */
    public string $bytes;

    /**
     * Keep the source pipe and unmodified bytes together; chunks are not lines.
     */
    public function __construct(OutputStream $stream, string $bytes)
    {
        $this->stream = $stream;
        $this->bytes = $bytes;
    }
}
