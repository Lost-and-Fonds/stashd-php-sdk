<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * RPC operations declared by the input-plugin interface.
 */
enum InputPluginMethod: string
{
    /**
     * Identifies the can resolve wire operation.
     */
    case CanResolve = 'stashd:plugin/input-plugin.can-resolve';
    /**
     * Identifies the resolve wire operation.
     */
    case Resolve = 'stashd:plugin/input-plugin.resolve';
    /**
     * Identifies the discover wire operation.
     */
    case Discover = 'stashd:plugin/input-plugin.discover';
    /**
     * Identifies the acquire wire operation.
     */
    case Acquire = 'stashd:plugin/input-plugin.acquire';
}
