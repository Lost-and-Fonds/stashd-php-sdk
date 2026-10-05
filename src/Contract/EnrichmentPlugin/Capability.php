<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

/**
 * An enrichment task and the options it accepts.
 */
final readonly class Capability
{
    /**
     * Create the capability.
     *
     * @param string $id Stable identifier used in later calls.
     * @param string $revision Revision of the capability definition.
     * @param list<ConfigurationOption> $options Settings selected for this work.
     */
    public function __construct(
        public string $id,
        public string $revision,
        public array $options,
    ) {}
}
