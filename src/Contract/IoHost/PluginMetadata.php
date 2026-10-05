<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.plugin-metadata contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class PluginMetadata
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $schema
     * @param string $json
     */
    public function __construct(
        public string $schema,
        public string $json,
    ) {}
}
