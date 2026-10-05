<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * A reference and optional title to include in an export.
 */
final readonly class CollectionEntry
{
    /**
     * Create the collection entry.
     *
     * @param string $reference Opaque reference interpreted by the service that issued it.
     * @param string|null $title Display title when supplied.
     */
    public function __construct(
        public string $reference,
        public ?string $title,
    ) {}
}
