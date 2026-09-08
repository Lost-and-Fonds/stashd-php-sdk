<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class AcquisitionResult
{
    /**
     * @param list<StagedArtifact> $artifacts
     * @param list<UnavailableArtifact> $unavailable
     */
    public function __construct(public array $artifacts = [], public array $unavailable = []) {}
}
