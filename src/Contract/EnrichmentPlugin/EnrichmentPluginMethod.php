<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * RPC operations declared by the enrichment-plugin interface.
 */
enum EnrichmentPluginMethod: string
{
    /**
     * Identifies the capabilities wire operation.
     */
    case Capabilities = 'stashd:plugin/enrichment-plugin.capabilities';
    /**
     * Identifies the enrich wire operation.
     */
    case Enrich = 'stashd:plugin/enrichment-plugin.enrich';
}
