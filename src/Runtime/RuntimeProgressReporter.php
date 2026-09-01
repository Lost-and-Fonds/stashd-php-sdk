<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\ProgressReporter;

final readonly class RuntimeProgressReporter implements ProgressReporter
{
    /** @param Closure $call */
    public function __construct(private Closure $call) {}

    public function report(string $stage): void
    {
        ($this->call)('event.progress', ['stage' => $stage]);
    }
}
