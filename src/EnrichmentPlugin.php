<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Enrichment\Capability;
use Stashd\PluginSdk\Enrichment\Item;
use Stashd\PluginSdk\Enrichment\Request;
use Stashd\PluginSdk\Enrichment\Result;

/**
 * Describe available item enrichment and run a selected capability.
 */
interface EnrichmentPlugin
{
    /**
     * Describe work this package can perform using only the supplied item.
     * @return list<Capability>
     */
    public function capabilities(Item $item): array;

    /**
     * Run one advertised capability and return metadata or saved files.
     */
    public function enrich(Request $request): Result;
}
