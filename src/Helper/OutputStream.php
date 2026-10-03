<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * Which helper output stream produced a chunk.
 */
enum OutputStream
{
    /**
     * Standard output. This is not emitted when stdout is being written to staged output.
     */
    case Stdout;

    /**
     * Standard error. This remains available when stdout is staged.
     */
    case Stderr;
}
