<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use Stashd\PluginSdk\Shared\Asset;
use Stashd\PluginSdk\Shared\Metadata;

/**
 * One selected saved item available during publication.
 */
final readonly class Item
{
    /**
     * Keep selected item information together without implying collection order.
     * @param string $id Stable saved item identifier.
     * @param list<Asset> $assets Saved files the plugin may publish.
     * @param list<Metadata> $metadata Metadata supplied with this item.
     */
    public function __construct(public string $id, public array $assets, public array $metadata) {}
}
