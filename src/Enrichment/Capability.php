<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

/**
 * A locally available enrichment operation with its accepted settings.
 */
final readonly class Capability
{
    /**
     * Describe one available capability.
     * @param string $id Plugin-defined capability identifier.
     * @param string $revision Version of its behavior and settings.
     * @param list<ConfigurationOption> $options Settings callers may select.
     */
    public function __construct(public string $id, public string $revision, public array $options = []) {}
}
