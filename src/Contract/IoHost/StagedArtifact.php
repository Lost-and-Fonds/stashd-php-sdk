<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Immutable io-host.staged-artifact contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class StagedArtifact
{
    /**
     * Canonical reference value; retained in contract order without normalization.
     * @var string
     */
    public string $reference;

    /**
     * Canonical media-type value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $mediaType;

    /**
     * Canonical size-bytes value; retained in contract order without normalization.
     * @var Unsigned64
     */
    public Unsigned64 $sizeBytes;

    /**
     * Canonical metadata value; retained in contract order without normalization.
     * @var list<PluginMetadata>
     */
    public array $metadata;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $reference
     * @param string|null $mediaType
     * @param Unsigned64 $sizeBytes
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        string $reference,
        ?string $mediaType,
        Unsigned64 $sizeBytes,
        array $metadata,
    ) {
        $this->reference = $reference;
        $this->mediaType = $mediaType;
        $this->sizeBytes = $sizeBytes;
        $this->metadata = $metadata;
    }
}
