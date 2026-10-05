<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentPlugin;

use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;

/**
 * A new asset and the source assets and activity used to create it.
 */
final readonly class DerivedAsset
{
    /**
     * Create the derived asset.
     *
     * @param StagedArtifact $artifact Completed output produced by this operation.
     * @param list<string> $derivedFrom Source asset IDs used to produce this asset.
     * @param string $activity Plugin-defined name of the work that produced the asset.
     * @param string $activityVersion Version of the activity that produced the asset.
     */
    public function __construct(
        public StagedArtifact $artifact,
        public array $derivedFrom,
        public string $activity,
        public string $activityVersion,
    ) {}
}
