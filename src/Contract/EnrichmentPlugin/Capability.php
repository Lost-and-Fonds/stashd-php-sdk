<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * Immutable enrichment-plugin.capability contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Capability
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param string $revision
     * @param list<ConfigurationOption> $options
     */
    public function __construct(
        public string $id,
        public string $revision,
        public array $options,
    ) {}
}
