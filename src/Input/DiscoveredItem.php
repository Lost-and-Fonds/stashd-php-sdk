<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Shared\Metadata;

/**
 * An item that can later be acquired using its stable ID and opaque reference.
 */
final readonly class DiscoveredItem
{
    /**
     * Describe a discoverable item without relying on process-local state.
     *
     * @param string $id Stable item identity used for a later acquisition.
     * @param string $reference Opaque reference the Input uses to retrieve the item.
     * @param string|null $delegation Handoff reference for another Input plugin.
     * @param int|null $sizeBytes Known or estimated total bytes.
     * @param bool $sizeEstimated Whether the supplied size is an estimate.
     * @param list<Metadata> $metadata Plugin-owned facets associated with the item.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $delegation = null,
        public ?int $sizeBytes = null,
        public bool $sizeEstimated = false,
        public array $metadata = [],
    ) {}
}
