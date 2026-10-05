<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.preserved-asset contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class PreservedAsset
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
     * Canonical media-type value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $mediaType;

    /**
     * Canonical size-bytes value; retained in contract order without normalization.
     * @var string
     */
    public string $sizeBytes;

    /**
     * Canonical metadata value; retained in contract order without normalization.
     * @var list<PluginMetadata>
     */
    public array $metadata;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param string $reference
     * @param string|null $mediaType
     * @param string $sizeBytes
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        string $id,
        string $reference,
        ?string $mediaType,
        string $sizeBytes,
        array $metadata,
    ) {
        $this->id = $id;
        $this->reference = $reference;
        $this->mediaType = $mediaType;
        $this->sizeBytes = $sizeBytes;
        $this->metadata = $metadata;
    }
}
