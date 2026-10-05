<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * A named setting for finding or saving items.
 */
final readonly class InputOption
{
    /**
     * Create the input option.
     *
     * @param string $key Name of the setting supplied by the plugin.
     * @param OptionValue $value Value associated with this entry.
     */
    public function __construct(
        public string $key,
        public OptionValue $value,
    ) {}
}
