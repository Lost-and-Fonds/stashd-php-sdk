<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Immutable input-plugin.resolved-input contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class ResolvedInput
{
    /**
     * Canonical id value; retained in contract order without normalization.
     * @var string
     */
    public string $id;

    /**
     * Canonical canonical-reference value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $canonicalReference;

    /**
     * Canonical estimated-item-count value; retained in contract order without normalization.
     * @var int|null
     */
    public ?int $estimatedItemCount;

    /**
     * Canonical size-bytes value; retained in contract order without normalization.
     * @var Unsigned64|null
     */
    public ?\Stashd\PluginSdk\Shared\Unsigned64 $sizeBytes;

    /**
     * Canonical size-estimated value; retained in contract order without normalization.
     * @var bool
     */
    public bool $sizeEstimated;

    /**
     * Canonical metadata value; retained in contract order without normalization.
     * @var list<PluginMetadata>
     */
    public array $metadata;

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
        string $id,
        ?string $canonicalReference,
        ?int $estimatedItemCount,
        ?Unsigned64 $sizeBytes,
        bool $sizeEstimated,
        array $metadata,
    ) {
        $this->id = $id;
        $this->canonicalReference = $canonicalReference;
        $this->estimatedItemCount = $estimatedItemCount;
        $this->sizeBytes = $sizeBytes;
        $this->sizeEstimated = $sizeEstimated;
        $this->metadata = $metadata;
    }
}
