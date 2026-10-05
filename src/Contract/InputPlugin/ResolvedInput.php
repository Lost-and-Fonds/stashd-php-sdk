<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * An identified source that can be searched in a later call.
 */
final readonly class ResolvedInput
{
    /**
     * Create the resolved input.
     *
     * @param string $id Stable identifier used in later calls.
     * @param string|null $canonicalReference Opaque source reference that remains usable across calls; null when absent.
     * @param int|null $estimatedItemCount Estimated number of items; null when unknown.
     * @param string|null $sizeBytes Optional known or estimated total input size in bytes.
     * @param bool $sizeEstimated Whether the supplied size is an estimate.
     * @param list<PluginMetadata> $metadata Metadata facets supplied by the plugin.
     */
    public function __construct(
        public string $id,
        public ?string $canonicalReference,
        public ?int $estimatedItemCount,
        public ?string $sizeBytes,
        public bool $sizeEstimated,
        public array $metadata,
    ) {}
}
