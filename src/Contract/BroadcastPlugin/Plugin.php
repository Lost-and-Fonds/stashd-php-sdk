<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Typed author lifecycle surface for broadcast-plugin.
 * Values are independent of JSON framing and host process reuse.
 */
interface Plugin
{
    /**
     * Execute canonical publish using current invocation values only.
     * @param PublishRequest $request
     * @param DestinationConfiguration $configuration
     * @param list<CredentialBinding> $credentials
     * @return Publication|PluginError
     */
    public function publish(PublishRequest $request, DestinationConfiguration $configuration, array $credentials): Publication|PluginError;

    /**
     * Execute canonical operation using current invocation values only.
     * @param OperationRequest $request
     * @param list<CredentialBinding> $credentials
     * @return OperationResult|PluginError
     */
    public function operation(OperationRequest $request, array $credentials): OperationResult|PluginError;

}
