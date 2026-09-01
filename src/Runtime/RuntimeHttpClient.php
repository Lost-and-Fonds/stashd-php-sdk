<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\HttpClient;
use Stashd\PluginSdk\HttpResponse;

final readonly class RuntimeHttpClient implements HttpClient
{
    /** @param Closure $call */
    public function __construct(private Closure $call) {}

    public function request(string $method, string $url, array $headers = [], ?string $body = null, ?string $credential = null): HttpResponse
    {
        $result = ($this->call)('http.request', [
            'method' => strtoupper($method), 'url' => $url, 'headers' => $headers,
            'body' => $body, 'credential' => $credential,
        ]);

        if (! is_array($result)) {
            return new HttpResponse(0, [], null);
        }
        $resource = isset($result['resource']) && is_string($result['resource'])
            ? new RuntimeReadableResource($this->call, $result['resource'])
            : null;

        $responseHeaders = [];

        foreach (is_array($result['headers'] ?? null) ? $result['headers'] : [] as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $responseHeaders[$name] = $value;
            }
        }

        return new HttpResponse(
            is_int($result['status'] ?? null) ? $result['status'] : 0,
            $responseHeaders,
            is_scalar($result['body'] ?? null) ? (string) $result['body'] : null,
            $resource,
        );
    }
}
