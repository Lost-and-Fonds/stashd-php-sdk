<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * A selected configuration value.
 */
final readonly class ConfigurationValue
{
    /**
     * Create the configuration value.
     *
     * @param string $key Name of the setting supplied by the plugin.
     * @param string $value Value associated with this entry.
     */
    public function __construct(
        public string $key,
        public string $value,
    ) {}
}
