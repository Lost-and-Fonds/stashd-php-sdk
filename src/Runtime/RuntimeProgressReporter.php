<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\CapabilityUnavailableException;
use Stashd\PluginSdk\DiscoveredItem;
use Stashd\PluginSdk\ProgressReporter;
use Stashd\PluginSdk\WireMapper;

final readonly class RuntimeProgressReporter implements ProgressReporter
{
    /** @param Closure $call */
    public function __construct(private Closure $call, private bool $allowDiscovered = true) {}

    public function report(string $stage, ?float $fraction = null): void
    {
        $progress = ['stage' => $stage, 'fraction' => $fraction];
        ($this->call)('event.progress', $this->allowDiscovered ? ['progress' => $progress] : $progress);
    }

    public function discovered(DiscoveredItem $item): void
    {
        if (! $this->allowDiscovered) {
            throw new CapabilityUnavailableException('report-discovered is not available in this invocation.');
        }

        ($this->call)('event.discovered', ['item' => WireMapper::discoveredItem($item)]);
    }
}
