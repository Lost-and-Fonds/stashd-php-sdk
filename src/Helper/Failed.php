<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * Host or runtime failure ended the process, not a normal nonzero child exit.
 */
final readonly class Failed
{
    /**
     * Host-supplied failure detail, which is not a portable child exit status.
     */
    public string $detail;

    /**
     * Retain the diagnostic supplied with the terminal failure.
     */
    public function __construct(string $detail)
    {
        $this->detail = $detail;
    }
}
