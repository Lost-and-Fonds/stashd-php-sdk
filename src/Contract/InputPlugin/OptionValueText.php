<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * The text form of option value.
 */
final readonly class OptionValueText implements OptionValue
{
    /**
     * Data carried by this result.
     * @var string
     */
    public string $value;

    /**
     * Create this result with its associated data.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
