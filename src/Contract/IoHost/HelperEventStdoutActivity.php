<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Monotonic cumulative bytes accepted by the staged stdout writer.
 */
final readonly class HelperEventStdoutActivity implements HelperEvent
{
    /**
     * Monotonic cumulative bytes accepted by the staged stdout writer.
     * @var string
     */
    public string $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
