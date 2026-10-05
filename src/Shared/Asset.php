<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

/**
 * A saved file granted to the current plugin call for reading.
 */
final readonly class Asset
{
    /**
     * Describe a saved file available to the current plugin call.
     *
     * @param string $id Stable ID of the saved file.
     * @param string $reference Opaque read reference, not a filesystem path.
     * @param string|null $mediaType Representation type, when supplied.
     * @param int $sizeBytes Total bytes in the saved file.
     * @param list<Metadata> $metadata Plugin-owned metadata attached to this file.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $mediaType,
        public int $sizeBytes,
        public array $metadata = [],
    ) {}
}
