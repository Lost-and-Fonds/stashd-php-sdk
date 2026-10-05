<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * The boolean form of option value.
 */
final readonly class OptionValueBoolean implements OptionValue
{
    /**
     * Data carried by this result.
     * @var bool
     */
    public bool $value;

    /**
     * Create this result with its associated data.
     * @param bool $value
     */
    public function __construct(bool $value)
    {
        $this->value = $value;
    }
}
