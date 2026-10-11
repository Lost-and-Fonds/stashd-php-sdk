<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * One bounded helper poll. A null event without end means the helper is still pending.
 */
final readonly class Poll
{
    /**
     * Capture output and cancellation observed during this poll.
     * @param bool $cancelRequested Whether Core requested invocation cancellation.
     * @param bool $ended Whether the terminal event has already been consumed.
     * @param Output|StdoutActivity|Exited|Cancelled|TimedOut|Failed|null $event Accepted helper event, if available.
     */
    public function __construct(public bool $cancelRequested, public bool $ended, public Output|StdoutActivity|Exited|Cancelled|TimedOut|Failed|null $event) {}
}
