<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * Closed canonical cases for input-plugin.discovery-intent; spelling is protocol identity.
 */
enum DiscoveryIntent: string
{
    /**
     * Discover changes using an optional completed refresh baseline.
     */
    case Refresh = 'refresh';
    /**
     * Enumerate the logical Input without requiring a refresh baseline.
     */
    case Complete = 'complete';
}
