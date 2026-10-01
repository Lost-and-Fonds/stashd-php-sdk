<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * Closed canonical cases for input-plugin.discovery-intent; spelling is protocol identity.
 */
enum DiscoveryIntent: string
{
    case Refresh = 'refresh';
    case Complete = 'complete';
}
