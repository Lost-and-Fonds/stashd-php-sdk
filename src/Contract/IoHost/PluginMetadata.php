<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.plugin-metadata contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class PluginMetadata
{
    /**
     * Canonical schema value; retained in contract order without normalization.
     * @var string
     */
    public string $schema;

    /**
     * Canonical json value; retained in contract order without normalization.
     * @var string
     */
    public string $json;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $schema
     * @param string $json
     */
    public function __construct(
        string $schema,
        string $json,
    ) {
        $this->schema = $schema;
        $this->json = $json;
    }
}
