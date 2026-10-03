<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * How many stdout bytes the helper has written to staged output so far. This is activity, not progress.
 */
final readonly class StdoutActivity
{
    /**
     * Total staged stdout bytes accepted so far.
     */
    public Unsigned64 $bytes;

    /**
     * Create an activity event from the exact byte count.
     */
    public function __construct(Unsigned64 $bytes)
    {
        $this->bytes = $bytes;
    }
}
