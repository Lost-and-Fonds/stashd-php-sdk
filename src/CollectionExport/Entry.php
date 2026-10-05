<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Generic interchange entry with no source taxonomy or implied URI semantics.
 */
final readonly class Entry
{
    /**
     * Create an entry without normalizing its reference or presentation text.
     *
     * @param string $reference The opaque reference the selected exporter should read.
     * @param string|null $title The optional label for this entry in the exported file.
     */
    public function __construct(public string $reference, public ?string $title = null) {}
}
