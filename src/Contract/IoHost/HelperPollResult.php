<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Values describing helper poll result.
 */
final readonly class HelperPollResult
{
    /**
     * Create the helper poll result.
     *
     * @param bool $invocationCancelRequested The supplied invocation cancel requested.
     * @param HelperPollState $state The supplied state.
     */
    public function __construct(
        public bool $invocationCancelRequested,
        public HelperPollState $state,
    ) {}
}
