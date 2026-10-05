<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Immutable collection-export-plugin.exported-artifact contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class ExportedArtifact
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $filename
     * @param string $mediaType
     * @param list<int> $contents
     */
    public function __construct(
        public string $filename,
        public string $mediaType,
        public array $contents,
    ) {}
}
