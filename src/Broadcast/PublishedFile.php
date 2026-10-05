<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * A path relative to the destination, not to the host or saved-file storage.
 */
final readonly class PublishedFile
{
    /**
     * Describe one file produced for this publication.
     */
    public function __construct(
        /**
         * Saved item ID associated with this destination file, when relevant.
         */
        public ?string $itemId,
        /**
         * Saved Asset ID associated with this destination file, when relevant.
         */
        public ?string $assetId,
        /**
         * Path relative to the destination's namespace.
         */
        public string $relativePath,
    ) {}
}
