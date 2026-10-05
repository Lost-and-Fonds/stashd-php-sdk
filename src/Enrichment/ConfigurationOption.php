<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

use InvalidArgumentException;

/**
 * Settings accepted by one enrichment capability.
 */
final readonly class ConfigurationOption
{
    /**
     * Describe one single-choice setting.
     * @param string $key Key returned with the selected configuration.
     * @param string $label Label shown during enrichment setup.
     * @param bool $required Whether the caller must select a value.
     * @param list<ConfigurationChoice> $choices Non-empty values the caller may select.
     */
    public function __construct(public string $key, public string $label, public bool $required, public array $choices)
    {
        if ($choices === []) {
            throw new InvalidArgumentException('Configuration choices cannot be empty');
        }
    }
}
