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
     * Canonical title value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $title;

    /**
     * Canonical entries value; retained in contract order without normalization.
     * @var list<CollectionEntry>
     */
    public array $entries;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string|null $title
     * @param list<CollectionEntry> $entries
     */
    public function __construct(
        ?string $title,
        array $entries,
    ) {
        $this->title = $title;
        $this->entries = $entries;
    }
}
