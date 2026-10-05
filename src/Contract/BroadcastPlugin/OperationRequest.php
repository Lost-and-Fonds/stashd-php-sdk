<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Immutable broadcast-plugin.operation-request contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class OperationRequest
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $name
     * @param list<Setting> $settings
     * @param list<Setting> $payload
     */
    public function __construct(
        public string $name,
        public array $settings,
        public array $payload,
    ) {}
}
