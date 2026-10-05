<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\InputHost\DiscoveredItem;
use Stashd\PluginSdk\Contract\InputHost\InputDelegation;
use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Internal input-plugin methods used by the runtime.
 */
interface Plugin
{
    /**
     * Run resolve with the supplied values.
     * @param list<SourceValue> $source
     * @param list<CredentialBinding> $credentials
     * @return ResolvedInput|PluginError
     */
    public function resolve(array $source, array $credentials): ResolvedInput|PluginError;

    /**
     * Run resolve delegation with the supplied values.
     * @param InputDelegation $delegation
     * @param list<CredentialBinding> $credentials
     * @return ResolvedInput|PluginError
     */
    public function resolveDelegation(InputDelegation $delegation, array $credentials): ResolvedInput|PluginError;

    /**
     * Run discover with the supplied values.
     * @param DiscoveryRequest $request
     * @param list<CredentialBinding> $credentials
     * @return null|PluginError
     */
    public function discover(DiscoveryRequest $request, array $credentials): ?PluginError;

    /**
     * Run acquire with the supplied values.
     * @param DiscoveredItem $item
     * @param AcquisitionOptions $options
     * @return AcquisitionResult|PluginError
     */
    public function acquire(DiscoveredItem $item, AcquisitionOptions $options): AcquisitionResult|PluginError;

}
