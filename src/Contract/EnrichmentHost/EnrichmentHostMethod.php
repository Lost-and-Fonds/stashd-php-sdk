<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentHost;

/**
 * RPC operations declared by the enrichment-host interface.
 */
enum EnrichmentHostMethod: string
{
    /**
     * Identifies the open asset wire operation.
     */
    case OpenAsset = 'stashd:plugin/enrichment-host.open-asset';
}
