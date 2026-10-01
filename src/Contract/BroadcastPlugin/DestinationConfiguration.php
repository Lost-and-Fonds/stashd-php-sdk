<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Immutable broadcast-plugin.destination-configuration contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class DestinationConfiguration
{
    /**
     * Canonical settings value; retained in contract order without normalization.
     * @var list<Setting>
     */
    public array $settings;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<Setting> $settings
     */
    public function __construct(
        array $settings,
    ) {
        $this->settings = $settings;
    }
}
