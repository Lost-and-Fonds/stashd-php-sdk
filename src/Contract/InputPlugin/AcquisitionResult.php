<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * Immutable input-plugin.acquisition-result contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class AcquisitionResult
{
    /**
     * Canonical artifacts value; retained in contract order without normalization.
     * @var list<StagedArtifact>
     */
    public array $artifacts;

    /**
     * Canonical outcome value; retained in contract order without normalization.
     * @var PreservationOutcome
     */
    public PreservationOutcome $outcome;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<StagedArtifact> $artifacts
     * @param PreservationOutcome $outcome
     */
    public function __construct(
        array $artifacts,
        PreservationOutcome $outcome,
    ) {
        $this->artifacts = $artifacts;
        $this->outcome = $outcome;
    }
}
