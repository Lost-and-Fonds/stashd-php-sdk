<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * Immutable broadcast-plugin.publication contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Publication
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param StagedArtifact|null $artifact
     * @param FileReportStatus $files
     */
    public function __construct(
        public ?StagedArtifact $artifact,
        public FileReportStatus $files,
    ) {}
}
