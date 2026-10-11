<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * RPC operations declared by the broadcast-plugin interface.
 */
enum BroadcastPluginMethod: string
{
    /**
     * Identifies the publish wire operation.
     */
    case Publish = 'stashd:plugin/broadcast-plugin.publish';
    /**
     * Identifies the operation wire operation.
     */
    case Operation = 'stashd:plugin/broadcast-plugin.operation';
}
