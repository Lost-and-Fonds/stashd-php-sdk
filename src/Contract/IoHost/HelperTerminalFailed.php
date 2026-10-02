<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Host/runtime failure ended the process; its staged writer is discarded.
 */
final readonly class HelperTerminalFailed implements HelperTerminal
{
    /**
     * Host/runtime failure ended the process; its staged writer is discarded.
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
