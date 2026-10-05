<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * An option the caller can configure.
 */
final readonly class ConfigurationOption
{
    /**
     * Create the configuration option.
     *
     * @param string $key Name of the setting supplied by the plugin.
     * @param string $label Text shown to the caller for this choice.
     * @param bool $required Whether the caller must select a value.
     * @param list<ConfigurationChoice> $choices Values the caller may select.
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $required,
        public array $choices,
    ) {}
}
