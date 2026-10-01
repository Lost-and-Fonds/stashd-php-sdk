<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\EnrichmentHost\ItemContext;
use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Typed author lifecycle surface for enrichment-plugin.
 * Values are independent of JSON framing and host process reuse.
 */
interface Plugin
{
    /**
     * Execute canonical capabilities using current invocation values only.
     * @param ItemContext $context
     * @return list<Capability>
     */
    public function capabilities(ItemContext $context): array;

    /**
     * Execute canonical enrich using current invocation values only.
     * @param ItemContext $context
     * @param string $capabilityId
     * @param string $capabilityRevision
     * @param list<ConfigurationValue> $configuration
     * @param list<CredentialBinding> $credentials
     * @return EnrichmentResult|PluginError
     */
    public function enrich(ItemContext $context, string $capabilityId, string $capabilityRevision, array $configuration, array $credentials): EnrichmentResult|PluginError;

}
