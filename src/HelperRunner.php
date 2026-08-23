<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

interface HelperRunner
{
    /** @param list<string> $arguments */
    public function run(string $name, array $arguments = []): HelperResult;
}
