<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\DiscoveredItem;
use Stashd\PluginSdk\ProgressReporter;
use Stashd\PluginSdk\WireMapper;

final readonly class RuntimeProgressReporter implements ProgressReporter
{
    /** @param Closure $call */
    public function __construct(private Closure $call) {}

    public function report(string $stage, ?float $fraction = null, ?int $sizeBytes = null, bool $sizeEstimated = false): void
    {
        ($this->call)('event.progress', ['stage' => $stage, 'fraction' => $fraction, 'size_bytes' => $sizeBytes, 'size_estimated' => $sizeEstimated]);
    }

    public function discovered(DiscoveredItem $item): void
    {
        ($this->call)('event.discovered', ['item' => WireMapper::discoveredItem($item)]);
    }
}
