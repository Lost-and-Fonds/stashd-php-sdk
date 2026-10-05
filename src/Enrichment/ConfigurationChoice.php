<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

/**
 * One accepted configuration string and its label.
 */
final readonly class ConfigurationChoice
{
    /**
     * Describe one selectable value.
     * @param string $value Value supplied when the caller selects this choice.
     * @param string $label Text shown to the person choosing a value.
     */
    public function __construct(public string $value, public string $label) {}
}
