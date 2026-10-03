<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * The choices and setting changes returned by a Broadcast operation.
 */
final readonly class OperationResult
{
    /**
     * Choices to show the caller.
     * @var list<Choice>
     */
    public array $choices;

    /**
     * Destination setting values to update.
     * @var list<Setting>
     */
    public array $values;

    /**
     * Create the operation result.
     * @param list<Choice> $choices
     * @param list<Setting> $values
     */
    public function __construct(array $choices = [], array $values = [])
    {
        $this->choices = $choices;
        $this->values = $values;
    }
}
