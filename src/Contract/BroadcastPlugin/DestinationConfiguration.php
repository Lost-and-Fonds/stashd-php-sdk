<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Settings for a publication destination.
 */
final readonly class DestinationConfiguration
{
    /**
     * Create the destination configuration.
     *
     * @param list<Setting> $settings Configuration values for this operation.
     */
    public function __construct(
        public array $settings,
    ) {}
}
