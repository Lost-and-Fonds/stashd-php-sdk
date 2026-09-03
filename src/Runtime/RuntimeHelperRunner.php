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

    /** @param list<string> $arguments
     * @param callable(string, string): void|null $onOutput
     */
    public function run(string $name, array $arguments = [], ?callable $onOutput = null): HelperResult
    {
        $result = ($this->call)('helper.run', ['name' => $name, 'arguments' => $arguments], $onOutput);

        if (! is_array($result)) {
            throw new RuntimeException('Plugin helper returned an invalid response.');
        }

        if (! is_int($result['exit_code'] ?? null)) {
            throw new RuntimeException('Plugin helper returned an invalid exit code.');
        }

        return new HelperResult(
            $result['exit_code'],
            is_string($result['stdout'] ?? null) ? $result['stdout'] : '',
            is_string($result['stderr'] ?? null) ? $result['stderr'] : '',
        );
    }
}
