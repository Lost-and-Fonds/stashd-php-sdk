<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Immutable broadcast-plugin.choice contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Choice
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $value
     * @param string $label
     */
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
