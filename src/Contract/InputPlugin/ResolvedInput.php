<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Immutable input-plugin.resolved-input contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class ResolvedInput
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param string|null $canonicalReference
     * @param int|null $estimatedItemCount
     * @param Unsigned64|null $sizeBytes
     * @param bool $sizeEstimated
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        public string $id,
        public ?string $canonicalReference,
        public ?int $estimatedItemCount,
        public ?Unsigned64 $sizeBytes,
        public bool $sizeEstimated,
        public array $metadata,
    ) {}
}
