<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * Identifies the original pipe that produced a helper output chunk.
 */
enum OutputStream
{
    /**
     * Unstaged standard output bytes.
     */
    case Stdout;

    /**
     * Standard error bytes, also available when stdout is staged.
     */
    case Stderr;
}
