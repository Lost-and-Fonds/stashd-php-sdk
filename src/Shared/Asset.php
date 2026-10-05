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
     * @param Unsigned64 $sizeBytes Total bytes, including counts larger than a PHP integer.
     * @param list<Metadata> $metadata Plugin-owned metadata attached to this file.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $mediaType,
        public Unsigned64 $sizeBytes,
        public array $metadata = [],
    ) {}
}
