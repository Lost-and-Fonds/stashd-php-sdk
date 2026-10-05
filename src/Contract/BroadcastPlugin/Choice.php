<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * A selectable value and its display label.
 */
final readonly class Choice
{
    /**
     * Create the choice.
     *
     * @param string $value Value associated with this entry.
     * @param string $label Text shown to the caller for this choice.
     */
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
