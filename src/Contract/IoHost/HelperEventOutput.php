<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Live stdout or stderr bytes; staged stdout is never duplicated here.
 */
final readonly class HelperEventOutput implements HelperEvent
{
    /**
     * Live stdout or stderr bytes; staged stdout is never duplicated here.
     * @var HelperOutput
     */
    public HelperOutput $value;

    /**
     * Create this result with its associated data.
     * @param HelperOutput $value
     */
    public function __construct(HelperOutput $value)
    {
        $this->value = $value;
    }
}
