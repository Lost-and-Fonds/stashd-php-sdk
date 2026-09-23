<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final class NullProgressReporter implements ProgressReporter
{
    public function report(string $stage, ?float $fraction = null, ?int $sizeBytes = null, bool $sizeEstimated = false): void {}

    public function discovered(DiscoveredItem $item): void {}
}
