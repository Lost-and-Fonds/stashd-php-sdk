<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Immutable collection-export-plugin.collection contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Collection
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string|null $title
     * @param list<CollectionEntry> $entries
     */
    public function __construct(
        public ?string $title,
        public array $entries,
    ) {}
}
