<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Generic interchange entry with no source taxonomy or implied URI semantics.
 */
final readonly class Entry
{
    /**
     * Opaque plugin-interpreted entry reference, preserved verbatim.
     */
    public string $reference;

    /**
     * Optional presentation title; null is distinct from an empty title.
     */
    public ?string $title;

    /**
     * Create an entry without normalizing its reference or presentation text.
     */
    public function __construct(string $reference, ?string $title = null)
    {
        $this->reference = $reference;
        $this->title = $title;
    }
}
