<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\EnrichmentHost\ItemContext;
use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Internal enrichment-plugin methods used by the runtime.
 */
interface Plugin
{
    /**
     * Run capabilities with the supplied values.
     * @param ItemContext $context
     * @return list<Capability>
     */
    public function capabilities(ItemContext $context): array;

    /**
     * Run enrich with the supplied values.
     * @param ItemContext $context
     * @param string $capabilityId
     * @param string $capabilityRevision
     * @param list<ConfigurationValue> $configuration
     * @param list<CredentialBinding> $credentials
     * @return EnrichmentResult|PluginError
     */
    public function enrich(ItemContext $context, string $capabilityId, string $capabilityRevision, array $configuration, array $credentials): EnrichmentResult|PluginError;

}
