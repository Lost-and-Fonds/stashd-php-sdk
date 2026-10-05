<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * A titled collection of entries to export.
 */
final readonly class Collection
{
    /**
     * Create a collection, keeping entries in the supplied order.
     */
    public function __construct(
        /**
         * Display title when supplied; not an identifier.
         */
        public ?string $title,
        Entry ...$entries,
    ) {
        $this->entries = array_values($entries);
    }

    /**
     * Entries in export order; the collection may be empty.
     * @var list<Entry>
     */
    public array $entries;
}
