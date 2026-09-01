<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\Logger;

final readonly class RuntimeLogger implements Logger
{
    /** @param Closure $call */
    public function __construct(private Closure $call) {}

    public function info(string $message): void
    {
        ($this->call)('event.log', ['level' => 'info', 'message' => $message]);
    }

    public function error(string $message): void
    {
        ($this->call)('event.log', ['level' => 'error', 'message' => $message]);
    }
}
