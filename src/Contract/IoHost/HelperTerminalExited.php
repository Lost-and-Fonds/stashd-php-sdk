<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Normal exit returns the valid owned staged writer when stdout was staged.
 */
final readonly class HelperTerminalExited implements HelperTerminal
{
    /**
     * Normal exit returns the valid owned staged writer when stdout was staged.
     * @var HelperExit
     */
    public HelperExit $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param HelperExit $value
     */
    public function __construct(HelperExit $value)
    {
        $this->value = $value;
    }
}
