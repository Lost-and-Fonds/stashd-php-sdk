<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * A named operation and the values it needs.
 */
final readonly class OperationRequest
{
    /**
     * Create the operation request.
     *
     * @param string $name Name used to select this entry.
     * @param list<Setting> $settings Configuration values for this operation.
     * @param list<Setting> $payload Additional data for the selected operation.
     */
    public function __construct(
        public string $name,
        public array $settings,
        public array $payload,
    ) {}
}
