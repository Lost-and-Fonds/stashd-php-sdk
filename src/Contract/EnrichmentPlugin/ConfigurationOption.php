<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * Immutable enrichment-plugin.configuration-option contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class ConfigurationOption
{
    /**
     * Canonical key value; retained in contract order without normalization.
     * @var string
     */
    public string $key;

    /**
     * Canonical label value; retained in contract order without normalization.
     * @var string
     */
    public string $label;

    /**
     * Canonical required value; retained in contract order without normalization.
     * @var bool
     */
    public bool $required;

    /**
     * Canonical choices value; retained in contract order without normalization.
     * @var list<ConfigurationChoice>
     */
    public array $choices;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $key
     * @param string $label
     * @param bool $required
     * @param list<ConfigurationChoice> $choices
     */
    public function __construct(
        string $key,
        string $label,
        bool $required,
        array $choices,
    ) {
        $this->key = $key;
        $this->label = $label;
        $this->required = $required;
        $this->choices = $choices;
    }
}
