<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * Immutable enrichment-plugin.configuration-choice contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class ConfigurationChoice
{
    /**
     * Canonical value value; retained in contract order without normalization.
     * @var string
     */
    public string $value;

    /**
     * Canonical label value; retained in contract order without normalization.
     * @var string
     */
    public string $label;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $value
     * @param string $label
     */
    public function __construct(
        string $value,
        string $label,
    ) {
        $this->value = $value;
        $this->label = $label;
    }
}
