<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * The choices and setting changes returned by a Broadcast operation.
 */
final readonly class ActionResult
{
    /**
     * Create the operation result.
     * @param list<Choice> $choices
     * @param list<Setting> $values
     */
    public function __construct(
        /**
         * @var list<Choice> Choices to show the caller.
         */
        public array $choices = [],
        /**
         * @var list<Setting> Destination setting values to update.
         */
        public array $values = [],
    ) {}
}
