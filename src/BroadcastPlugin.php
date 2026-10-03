<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Broadcast\Operation;
use Stashd\PluginSdk\Broadcast\OperationResult;

/**
 * Implement Broadcast operations exposed by this plugin.
 */
interface BroadcastPlugin
{
    /**
     * Run one named Broadcast operation. Helpers contains only the capabilities available to this call.
     */
    public function operation(Operation $request, Helpers $helpers): OperationResult;
}
