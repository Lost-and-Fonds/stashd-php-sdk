<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class StagedArtifact
{
    public function __construct(public string $reference, public ?string $mediaType = null, public int $sizeBytes = 0, public ?string $role = null) {}
}
