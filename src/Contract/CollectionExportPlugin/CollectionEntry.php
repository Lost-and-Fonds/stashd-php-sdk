<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Immutable collection-export-plugin.collection-entry contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class CollectionEntry
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $reference
     * @param string|null $title
     */
    public function __construct(
        public string $reference,
        public ?string $title,
    ) {}
}
