<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Choices and values returned by an operation.
 */
final readonly class OperationResult
{
    /**
     * Create the operation result.
     *
     * @param list<Choice> $choices Values the caller may select.
     * @param list<Setting> $values Named values returned by the operation.
     */
    public function __construct(
        public array $choices,
        public array $values,
    ) {}
}
