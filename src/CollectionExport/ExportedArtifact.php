<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * An exported file returned directly as bytes.
 */
final readonly class ExportedArtifact
{
    /**
     * Create an exported file with a suggested name and media type.
     */
    public function __construct(
        /**
         * Suggested file name, not a path on the host.
         */
        public string $filename,
        /**
         * Media type of the exported file.
         */
        public string $mediaType,
        /**
         * File contents, which may contain binary bytes.
         */
        public string $contents,
    ) {}
}
