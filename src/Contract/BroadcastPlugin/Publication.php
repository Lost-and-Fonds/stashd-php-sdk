<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * Immutable broadcast-plugin.publication contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class Publication
{
    /**
     * Canonical artifact value; retained in contract order without normalization.
     * @var StagedArtifact|null
     */
    public ?\Stashd\PluginSdk\Contract\IoHost\StagedArtifact $artifact;

    /**
     * Canonical files value; retained in contract order without normalization.
     * @var FileReportStatus
     */
    public FileReportStatus $files;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param StagedArtifact|null $artifact
     * @param FileReportStatus $files
     */
    public function __construct(
        ?StagedArtifact $artifact,
        FileReportStatus $files,
    ) {
        $this->artifact = $artifact;
        $this->files = $files;
    }
}
