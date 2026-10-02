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
     * Construct this specific branch without string tags or raw wire objects.
     * @param HelperOutput $value
     */
    public function __construct(HelperOutput $value)
    {
        $this->value = $value;
    }
}
