<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * An exported file returned as bytes.
 */
final readonly class ExportedArtifact
{
    /**
     * Create the exported artifact.
     *
     * @param string $filename Suggested name for the exported file.
     * @param string $mediaType Media type when known; null when unspecified.
     * @param list<int> $contents Exported file bytes.
     */
    public function __construct(
        public string $filename,
        public string $mediaType,
        public array $contents,
    ) {}
}
