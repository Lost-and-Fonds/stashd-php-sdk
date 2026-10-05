<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * The number form of option value.
 */
final readonly class OptionValueNumber implements OptionValue
{
    /**
     * Data carried by this result.
     * @var int
     */
    public int $value;

    /**
     * Create this result with its associated data.
     * @param int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}
