<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * The host could not start or manage the helper normally.
 */
final readonly class Failed
{
    /**
     * Describe a host or runtime failure.
     * @param string $detail Diagnostic message supplied by the host.
     */
    public function __construct(public string $detail) {}
}
