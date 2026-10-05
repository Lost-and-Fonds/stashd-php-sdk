<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Broadcast\Action;
use Stashd\PluginSdk\Broadcast\ActionResult;
use Stashd\PluginSdk\Broadcast\Publication;
use Stashd\PluginSdk\Broadcast\Publish;

/**
 * Publish selected saved items or answer a named destination action.
 */
interface BroadcastPlugin
{
    /**
     * Publish selected items in one complete call.
     */
    public function publish(Publish $request): Publication;

    /**
     * Run a plugin-defined interactive action without publishing items.
     */
    public function action(Action $request): ActionResult;
}
