<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final class NullLogger implements Logger
{
    public function log(string $message): void {}
}
