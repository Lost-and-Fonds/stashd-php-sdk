<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * Immutable enrichment-plugin.derived-asset contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class DerivedAsset
{
    /**
     * Canonical artifact value; retained in contract order without normalization.
     * @var StagedArtifact
     */
    public StagedArtifact $artifact;

    /**
     * Canonical derived-from value; retained in contract order without normalization.
     * @var list<string>
     */
    public array $derivedFrom;

    /**
     * Canonical activity value; retained in contract order without normalization.
     * @var string
     */
    public string $activity;

    /**
     * Canonical activity-version value; retained in contract order without normalization.
     * @var string
     */
    public string $activityVersion;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param StagedArtifact $artifact
     * @param list<string> $derivedFrom
     * @param string $activity
     * @param string $activityVersion
     */
    public function __construct(
        StagedArtifact $artifact,
        array $derivedFrom,
        string $activity,
        string $activityVersion,
    ) {
        $this->artifact = $artifact;
        $this->derivedFrom = $derivedFrom;
        $this->activity = $activity;
        $this->activityVersion = $activityVersion;
    }
}
