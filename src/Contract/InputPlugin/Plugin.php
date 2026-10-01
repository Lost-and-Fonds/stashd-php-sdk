<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\InputHost\DiscoveredItem;
use Stashd\PluginSdk\Contract\InputHost\InputDelegation;
use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Typed author lifecycle surface for input-plugin.
 * Values are independent of JSON framing and host process reuse.
 */
interface Plugin
{
    /**
     * Execute canonical resolve using current invocation values only.
     * @param list<SourceValue> $source
     * @param list<CredentialBinding> $credentials
     * @return ResolvedInput|PluginError
     */
    public function resolve(array $source, array $credentials): ResolvedInput|PluginError;

    /**
     * Execute canonical resolve-delegation using current invocation values only.
     * @param InputDelegation $delegation
     * @param list<CredentialBinding> $credentials
     * @return ResolvedInput|PluginError
     */
    public function resolveDelegation(InputDelegation $delegation, array $credentials): ResolvedInput|PluginError;

    /**
     * Execute canonical discover using current invocation values only.
     * @param DiscoveryRequest $request
     * @param list<CredentialBinding> $credentials
     * @return null|PluginError
     */
    public function discover(DiscoveryRequest $request, array $credentials): ?PluginError;

    /**
     * Execute canonical acquire using current invocation values only.
     * @param DiscoveredItem $item
     * @param AcquisitionOptions $options
     * @return AcquisitionResult|PluginError
     */
    public function acquire(DiscoveredItem $item, AcquisitionOptions $options): AcquisitionResult|PluginError;

}
