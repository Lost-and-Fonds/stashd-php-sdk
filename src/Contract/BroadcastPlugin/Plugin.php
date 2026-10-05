<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Internal broadcast-plugin methods used by the runtime.
 */
interface Plugin
{
    /**
     * Run publish with the supplied values.
     * @param PublishRequest $request
     * @param DestinationConfiguration $configuration
     * @param list<CredentialBinding> $credentials
     * @return Publication|PluginError
     */
    public function publish(PublishRequest $request, DestinationConfiguration $configuration, array $credentials): Publication|PluginError;

    /**
     * Run operation with the supplied values.
     * @param OperationRequest $request
     * @param list<CredentialBinding> $credentials
     * @return OperationResult|PluginError
     */
    public function operation(OperationRequest $request, array $credentials): OperationResult|PluginError;

}
