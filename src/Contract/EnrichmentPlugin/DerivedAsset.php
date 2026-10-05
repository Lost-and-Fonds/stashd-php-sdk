<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * Immutable enrichment-plugin.derived-asset contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class DerivedAsset
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param StagedArtifact $artifact
     * @param list<string> $derivedFrom
     * @param string $activity
     * @param string $activityVersion
     */
    public function __construct(
        public StagedArtifact $artifact,
        public array $derivedFrom,
        public string $activity,
        public string $activityVersion,
    ) {}
}
