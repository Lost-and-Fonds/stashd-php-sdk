<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * Canonical number branch of input-plugin.option-value.
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
