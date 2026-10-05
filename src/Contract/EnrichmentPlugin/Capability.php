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
     * Canonical id value; retained in contract order without normalization.
     * @var string
     */
    public string $id;

    /**
     * Canonical revision value; retained in contract order without normalization.
     * @var string
     */
    public string $revision;

    /**
     * Canonical options value; retained in contract order without normalization.
     * @var list<ConfigurationOption>
     */
    public array $options;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     * @param string $revision
     * @param list<ConfigurationOption> $options
     */
    public function __construct(
        string $id,
        string $revision,
        array $options,
    ) {
        $this->id = $id;
        $this->revision = $revision;
        $this->options = $options;
    }
}
