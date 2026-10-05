<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * A chunk of bytes written by the helper to stdout or stderr.
 */
final readonly class Output
{
    /**
     * Create one helper output event. A chunk is not necessarily a complete line.
     * @param OutputStream $stream Which output stream produced the bytes.
     * @param string $bytes Raw bytes exactly as written by the helper, including carriage returns or non-UTF-8 data.
     */
    public function __construct(public OutputStream $stream, public string $bytes) {}
}
