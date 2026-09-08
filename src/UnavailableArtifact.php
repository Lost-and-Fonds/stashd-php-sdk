<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class UnavailableArtifact
{
    public function __construct(public ArtifactRole $role, public bool $permanent, public string $message) {}
}
