<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

interface ProgressReporter
{
    public function report(string $stage, ?float $fraction = null): void;

    public function discovered(DiscoveredItem $item): void;
}
