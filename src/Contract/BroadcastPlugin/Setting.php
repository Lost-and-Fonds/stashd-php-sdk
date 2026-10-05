<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * A named configuration value.
 */
final readonly class Setting
{
    /**
     * Create the setting.
     *
     * @param string $key Name of the setting supplied by the plugin.
     * @param OptionValue $value Value associated with this entry.
     */
    public function __construct(
        public string $key,
        public OptionValue $value,
    ) {}
}
