<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class HelperResult
{
    public function __construct(
        public int $exitCode,
        public string $stdout = '',
        public string $stderr = '',
    ) {}
}
