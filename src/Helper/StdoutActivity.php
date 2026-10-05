<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * How many stdout bytes the helper has written to staged output so far. This is activity, not progress.
 */
final readonly class StdoutActivity
{
    /**
     * Create an activity event from the exact byte count.
     */
    public function __construct(
        /**
         * Total staged stdout bytes accepted so far.
         */
        public int $bytes,
    ) {}
}
