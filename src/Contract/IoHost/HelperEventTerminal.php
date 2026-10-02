<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Sole process outcome after all accepted output and final staged activity.
 */
final readonly class HelperEventTerminal implements HelperEvent
{
    /**
     * Sole process outcome after all accepted output and final staged activity.
     * @var HelperTerminal
     */
    public HelperTerminal $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param HelperTerminal $value
     */
    public function __construct(HelperTerminal $value)
    {
        $this->value = $value;
    }
}
