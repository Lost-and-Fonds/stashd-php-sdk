<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Canonical boolean branch of collection-export-plugin.option-value.
 */
final readonly class OptionValueBoolean implements OptionValue
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var bool
     */
    public bool $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param bool $value
     */
    public function __construct(bool $value)
    {
        $this->value = $value;
    }
}
