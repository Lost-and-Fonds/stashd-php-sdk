<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One option a Broadcast operation can return to the caller.
 */
final readonly class Choice
{
    /**
     * Value returned when the caller chooses this option.
     */
    public string $value;

    /**
     * Label shown to the caller.
     */
    public string $label;

    /**
     * Create one selectable option.
     */
    public function __construct(string $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
    }
}
