<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Cumulative stdout bytes accepted by the staged writer, not a percentage.
 */
final readonly class StdoutActivity
{
    /**
     * Exact cumulative byte count, including values beyond PHP's signed integer range.
     */
    public Unsigned64 $bytes;

    /**
     * Preserve the host's cumulative count without rounding.
     */
    public function __construct(Unsigned64 $bytes)
    {
        $this->bytes = $bytes;
    }
}
