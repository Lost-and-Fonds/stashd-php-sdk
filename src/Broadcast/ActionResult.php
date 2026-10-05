<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * Choices and changed settings returned by an interactive destination action.
 */
final readonly class ActionResult
{
    /**
     * Provide choices and optional settings selected by this action.
     * @param list<Choice> $choices Options shown to the caller.
     * @param list<Setting> $values Destination changes returned to the caller.
     */
    public function __construct(public array $choices = [], public array $values = []) {}
}
