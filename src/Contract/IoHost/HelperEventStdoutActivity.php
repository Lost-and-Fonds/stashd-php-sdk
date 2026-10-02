<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Monotonic cumulative bytes accepted by the staged stdout writer.
 */
final readonly class HelperEventStdoutActivity implements HelperEvent
{
    /**
     * Monotonic cumulative bytes accepted by the staged stdout writer.
     * @var Unsigned64
     */
    public Unsigned64 $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param Unsigned64 $value
     */
    public function __construct(Unsigned64 $value)
    {
        $this->value = $value;
    }
}
