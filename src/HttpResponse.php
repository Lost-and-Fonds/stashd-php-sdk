<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class HttpResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        public int $status,
        public array $headers = [],
        public string $body = '',
    ) {}

    public function body(): string
    {
        return $this->body;
    }
}
