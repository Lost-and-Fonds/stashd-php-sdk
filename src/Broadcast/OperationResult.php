<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * Choices and settings returned from an interactive destination operation.
 */
final readonly class OperationResult
{
    /**
     * Ordered choices offered to the caller.
     * @var list<Choice>
     */
    public array $choices;

    /**
     * Ordered plugin-defined setting updates.
     * @var list<Setting>
     */
    public array $values;

    /**
     * Return only values intentionally produced by this operation.
     * @param list<Choice> $choices
     * @param list<Setting> $values
     */
    public function __construct(array $choices = [], array $values = [])
    {
        $this->choices = $choices;
        $this->values = $values;
    }
}
