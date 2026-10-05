<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

use Stashd\PluginSdk\Shared\Asset;
use Stashd\PluginSdk\Shared\Metadata;

/**
 * Saved item details supplied when discovering or running enrichment.
 */
final readonly class Item
{
    /**
     * Describe the saved item and the information available to enrichment.
     * @param string $id Stable identifier used for this saved item.
     * @param list<Asset> $assets Files the capability may read.
     * @param list<Metadata> $metadata Metadata currently attached to the item.
     */
    public function __construct(public string $id, public array $assets, public array $metadata) {}
}
