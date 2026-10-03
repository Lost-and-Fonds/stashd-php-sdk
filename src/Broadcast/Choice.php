<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One selectable value offered by an interactive destination operation.
 */
final readonly class Choice
{
    /**
     * Plugin-defined choice identity.
     */
    public string $value;

    /**
     * Human-readable label shown to the caller.
     */
    public string $label;

    /**
     * Associate a choice identity with its label.
     */
    public function __construct(string $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
    }
}
