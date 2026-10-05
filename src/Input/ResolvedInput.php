<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Shared\Metadata;

/**
 * A stable input identity from which discovery can start independently.
 */
final readonly class ResolvedInput
{
    /**
     * Describe a resolved input without depending on process-local state.
     *
     * @param string $id Stable identity used in later discovery calls.
     * @param string|null $canonicalReference Opaque source reference, when available.
     * @param int|null $estimatedItemCount Approximate number of available items.
     * @param int|null $sizeBytes Approximate or exact byte count.
     * @param bool $sizeEstimated Whether the supplied size is an estimate.
     * @param list<Metadata> $metadata Plugin-owned metadata describing the source.
     */
    public function __construct(
        public string $id,
        public ?string $canonicalReference = null,
        public ?int $estimatedItemCount = null,
        public ?int $sizeBytes = null,
        public bool $sizeEstimated = false,
        public array $metadata = [],
    ) {}
}
