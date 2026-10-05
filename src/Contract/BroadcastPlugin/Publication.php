<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * The output of publishing a collection.
 */
final readonly class Publication
{
    /**
     * Create the publication.
     *
     * @param StagedArtifact|null $artifact Completed output produced by this operation.
     * @param FileReportStatus $files Whether the publication supplied a complete file report.
     */
    public function __construct(
        public ?StagedArtifact $artifact,
        public FileReportStatus $files,
    ) {}
}
