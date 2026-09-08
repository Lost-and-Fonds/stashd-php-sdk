<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class AcquisitionOptions
{
    /** @param list<InputOption> $options
     * @param list<ArtifactRole>|null $requestedRoles null preserves legacy full acquisition
     */
    public function __construct(public MediaKind $mediaKind, public array $options = [], public ?array $requestedRoles = null) {}
}
