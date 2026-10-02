<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Immutable input-host.discovery-refresh-state contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class DiscoveryRefreshState
{
    /**
     * Canonical value value; retained in contract order without normalization.
     * @var string
     */
    public string $value;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $value
     */
    public function __construct(
        string $value,
    ) {
        $this->value = $value;
    }
}
