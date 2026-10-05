<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * Immutable input-host.discovered-item contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class DiscoveredItem
{
    /**
     * Canonical id value; retained in contract order without normalization.
     * @var string
     */
    public string $id;

    /**
     * Canonical reference value; retained in contract order without normalization.
     * @var string
     */
    public string $reference;

    /**
     * Canonical delegation value; retained in contract order without normalization.
     * @var InputDelegation|null
     */
    public ?InputDelegation $delegation;

    /**
     * Canonical size-bytes value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $sizeBytes;

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
     * @param string $reference
     * @param InputDelegation|null $delegation
     * @param string|null $sizeBytes
     * @param bool $sizeEstimated
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        string $id,
        string $reference,
        ?InputDelegation $delegation,
        ?string $sizeBytes,
        bool $sizeEstimated,
        array $metadata,
    ) {
        $this->id = $id;
        $this->reference = $reference;
        $this->delegation = $delegation;
        $this->sizeBytes = $sizeBytes;
        $this->sizeEstimated = $sizeEstimated;
        $this->metadata = $metadata;
    }
}
