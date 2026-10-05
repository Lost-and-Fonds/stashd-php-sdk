<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Immutable collection-export-plugin.setting contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Setting
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $key
     * @param OptionValue $value
     */
    public function __construct(
        public string $key,
        public OptionValue $value,
    ) {}
}
