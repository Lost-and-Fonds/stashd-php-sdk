<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Immutable io-host.preserved-asset contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class PreservedAsset
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param string $reference
     * @param string|null $mediaType
     * @param Unsigned64 $sizeBytes
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $mediaType,
        public Unsigned64 $sizeBytes,
        public array $metadata,
    ) {}
}
