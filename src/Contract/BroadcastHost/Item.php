<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;

/**
 * An item and its saved assets and metadata.
 */
final readonly class Item
{
    /**
     * Create the item.
     *
     * @param string $id Stable identifier used in later calls.
     * @param list<PreservedAsset> $assets Assets associated with this result.
     * @param list<PluginMetadata> $metadata Metadata facets supplied by the plugin.
     */
    public function __construct(
        public string $id,
        public array $assets,
        public array $metadata,
    ) {}
}
