<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * A titled list of entries to export.
 */
final readonly class Collection
{
    /**
     * Create the collection.
     *
     * @param string|null $title Display title when supplied.
     * @param list<CollectionEntry> $entries Collection entries in export order.
     */
    public function __construct(
        public ?string $title,
        public array $entries,
    ) {}
}
