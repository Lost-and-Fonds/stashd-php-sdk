<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * The host could not start or manage the helper normally.
 */
final readonly class Failed
{
    /**
     * Diagnostic message supplied by the host.
     */
    public string $detail;

    /**
     * Describe a host or runtime failure.
     */
    public function __construct(string $detail)
    {
        $this->detail = $detail;
    }
}
