<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

interface HelperRunner
{
    /** @param list<string> $arguments
     * @param callable(string, string): void|null $onOutput
     */
    public function run(string $name, array $arguments = [], ?callable $onOutput = null): HelperResult;
}
