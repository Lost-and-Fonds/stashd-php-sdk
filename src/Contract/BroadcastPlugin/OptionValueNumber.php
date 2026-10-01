<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Canonical number branch of broadcast-plugin.option-value.
 */
final readonly class OptionValueNumber implements OptionValue
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var int
     */
    public int $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}
