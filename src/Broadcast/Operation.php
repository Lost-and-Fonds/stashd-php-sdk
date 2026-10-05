<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * A request to run one named Broadcast operation.
 */
final readonly class Operation
{
    /**
     * Create an operation request.
     * @param string $name The plugin-defined action to run.
     * @param list<Setting> $settings Current destination configuration.
     * @param list<Setting> $payload Values supplied specifically for this action.
     */
    public function __construct(
        public string $name,
        public array $settings,
        public array $payload,
    ) {}
}
