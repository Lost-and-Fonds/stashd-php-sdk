<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Shared\Unsigned64;

/**
 * Immutable input-host.discovered-item contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class DiscoveredItem
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param string $reference
     * @param InputDelegation|null $delegation
     * @param Unsigned64|null $sizeBytes
     * @param bool $sizeEstimated
     * @param list<PluginMetadata> $metadata
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?InputDelegation $delegation,
        public ?Unsigned64 $sizeBytes,
        public bool $sizeEstimated,
        public array $metadata,
    ) {}
}
