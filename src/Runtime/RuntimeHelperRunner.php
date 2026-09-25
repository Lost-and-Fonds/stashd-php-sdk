<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use RuntimeException;
use Stashd\PluginSdk\HelperResult;
use Stashd\PluginSdk\HelperRunner;

final readonly class RuntimeHelperRunner implements HelperRunner
{
    /** @param Closure $call */
    public function __construct(private Closure $call) {}

    /** @param list<string> $arguments */
    public function run(string $name, array $arguments = []): HelperResult
    {
        $result = ($this->call)('helper.run', ['name' => $name, 'args' => $arguments]);

        if (! is_array($result)) {
            throw new RuntimeException('Plugin helper returned an invalid response.');
        }

        if (! is_int($result['exit-code'] ?? null)
            || $result['exit-code'] < -2_147_483_648
            || $result['exit-code'] > 2_147_483_647
            || ! is_string($result['stdout'] ?? null)
            || ! is_string($result['stderr'] ?? null)) {
            throw new RuntimeException('Plugin helper returned an invalid result.');
        }

        return new HelperResult(
            $result['exit-code'],
            $result['stdout'],
            $result['stderr'],
        );
    }
}
