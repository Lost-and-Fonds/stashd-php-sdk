<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Immutable collection-export-plugin.exported-artifact contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class ExportedArtifact
{
    /**
     * Canonical filename value; retained in contract order without normalization.
     * @var string
     */
    public string $filename;

    /**
     * Canonical media-type value; retained in contract order without normalization.
     * @var string
     */
    public string $mediaType;

    /**
     * Canonical contents value; retained in contract order without normalization.
     * @var list<int>
     */
    public array $contents;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $filename
     * @param string $mediaType
     * @param list<int> $contents
     */
    public function __construct(
        string $filename,
        string $mediaType,
        array $contents,
    ) {
        $this->filename = $filename;
        $this->mediaType = $mediaType;
        $this->contents = $contents;
    }
}
