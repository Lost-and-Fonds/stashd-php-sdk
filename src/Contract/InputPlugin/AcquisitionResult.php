<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * Saved outputs and a description of any missing work.
 */
final readonly class AcquisitionResult
{
    /**
     * Create the acquisition result.
     *
     * @param list<StagedArtifact> $artifacts Completed outputs returned for the host to save.
     * @param PreservationOutcome $outcome Whether all intended work completed, including any known gaps.
     */
    public function __construct(
        public array $artifacts,
        public PreservationOutcome $outcome,
    ) {}
}
