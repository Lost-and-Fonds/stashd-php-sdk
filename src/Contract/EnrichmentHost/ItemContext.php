<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;

/**
 * An item and the saved assets available for enrichment.
 */
final readonly class ItemContext
{
    /**
     * Create the item context.
     *
     * @param string $itemId Identifier of the item this result belongs to.
     * @param list<PreservedAsset> $assets Assets associated with this result.
     * @param list<PluginMetadata> $metadata Metadata facets supplied by the plugin.
     */
    public function __construct(
        public string $itemId,
        public array $assets,
        public array $metadata,
    ) {}
}
