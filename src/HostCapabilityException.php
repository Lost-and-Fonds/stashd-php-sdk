<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use RuntimeException;

final class HostCapabilityException extends RuntimeException
{
    public function __construct(
        public readonly string $method,
        public readonly string $tag,
        public readonly mixed $value = null,
    ) {
        parent::__construct(is_string($value) ? $value : $tag);
    }
}
