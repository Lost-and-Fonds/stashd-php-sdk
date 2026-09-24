<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final class UnavailableHttpClient implements HttpClient
{
    /** @param array<string, string> $headers */
    public function request(string $method, string $url, array $headers = [], ?string $body = null, ?string $credential = null): HttpResponse
    {
        throw new CapabilityUnavailableException('HTTP capability is unavailable in this invocation.');
    }
}
