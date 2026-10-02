<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Identifies which child output pipe delivered diagnostic bytes.
 */
enum HelperOutputStream: string
{
    /**
     * Unstaged stdout byte channel; never emitted with staged stdout.
     */
    case Stdout = 'stdout';
    /**
     * Live stderr byte channel, including when stdout is staged.
     */
    case Stderr = 'stderr';
}
