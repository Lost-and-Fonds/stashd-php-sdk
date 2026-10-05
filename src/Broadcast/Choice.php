<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One option a Broadcast operation can return to the caller.
 */
final readonly class Choice
{
    /**
     * Create one selectable option.
     * @param string $value The value sent back when selected.
     * @param string $label The option text shown in the caller's interface.
     */
    public function __construct(public string $value, public string $label) {}
}
