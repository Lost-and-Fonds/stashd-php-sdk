<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Broadcast\Operation;
use Stashd\PluginSdk\Broadcast\OperationResult;

/**
 * A Broadcast operation that can run approved helpers for the current destination.
 */
interface BroadcastPlugin
{
    /**
     * Handle one host-selected operation with helpers scoped to this call.
     * The operation name is plugin-defined and remains unchanged.
     */
    public function operation(Operation $request, Helpers $helpers): OperationResult;
}
