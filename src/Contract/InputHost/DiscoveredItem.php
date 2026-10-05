<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * An item the plugin can later retrieve using its stable ID and opaque reference.
 */
final readonly class DiscoveredItem
{
    /**
     * Create the discovered item.
     *
     * @param string $id Stable item identity used when the host requests acquisition.
     * @param string $reference Opaque value the plugin uses to retrieve the item; preserved exactly.
     * @param InputDelegation|null $delegation Optional opaque handoff reference for another Input plugin.
     * @param string|null $sizeBytes Optional known or estimated total item size in bytes.
     * @param bool $sizeEstimated Whether the supplied item size is an estimate.
     * @param list<PluginMetadata> $metadata Plugin-owned metadata facets associated with this item.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?InputDelegation $delegation,
        public ?string $sizeBytes,
        public bool $sizeEstimated,
        public array $metadata,
    ) {}
}
