<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * A selectable configuration value and its display label.
 */
final readonly class ConfigurationChoice
{
    /**
     * Create the configuration choice.
     *
     * @param string $value Value associated with this entry.
     * @param string $label Text shown to the caller for this choice.
     */
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
