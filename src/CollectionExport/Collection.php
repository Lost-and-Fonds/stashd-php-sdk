<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Bounded inline interchange input; encoded transport size is checked separately by RPC framing.
 */
final readonly class Collection
{
    /**
     * Optional collection presentation title, not a durable identifier.
     */
    public ?string $title;

    /**
     * Ordered generic entries; an empty collection is valid.
     * @var list<Entry>
     */
    public array $entries;

    /**
     * Build a typed ordered collection without inventing a universal cardinality ceiling.
     */
    public function __construct(?string $title, Entry ...$entries)
    {
        $this->title = $title;
        $this->entries = array_values($entries);
    }
}
