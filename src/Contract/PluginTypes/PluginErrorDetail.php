<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\PluginTypes;

/**
 * An error explanation and whether retrying may help.
 */
final readonly class PluginErrorDetail
{
    /**
     * Create the plugin error detail.
     *
     * @param string $message Human-readable explanation of what happened.
     * @param bool $retryable Whether retrying the failed work may succeed.
     */
    public function __construct(
        public string $message,
        public bool $retryable,
    ) {}
}
