<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Inline interchange output, separate from host-staged artifact ownership and adoption.
 */
final readonly class ExportedArtifact
{
    /**
     * Construct inline bytes without imposing a hidden transport or artifact-size ceiling.
     */
    public function __construct(
        /**
         * Plugin-selected exported filename, not a host filesystem path.
         */
        public string $filename,
        /**
         * Declared representation media type, preserved as supplied.
         */
        public string $mediaType,
        /**
         * Binary PHP string, encoded as canonical integer byte lists.
         */
        public string $contents,
    ) {}
}
