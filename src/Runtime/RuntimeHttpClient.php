<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\HttpClient;
use Stashd\PluginSdk\HttpResponse;
use Stashd\PluginSdk\InvalidPluginResultException;

final readonly class RuntimeHttpClient implements HttpClient
{
    /** @param Closure $call */
    public function __construct(private Closure $call) {}

    public function request(string $method, string $url, array $headers = [], ?string $body = null, ?string $credential = null): HttpResponse
    {
        $method = strtolower($method);

        if (! in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
            throw new \InvalidArgumentException('HTTP method is not part of the plugin contract.');
        }

        $result = ($this->call)('http.request', [
            'method' => $method, 'url' => $url, 'headers' => array_map(
                static fn(string $name, string $value): array => ['name' => $name, 'value' => $value],
                array_keys($headers),
                array_values($headers),
            ),
            'body' => $body === null ? [] : array_values(unpack('C*', $body) ?: []),
            'credential' => $credential,
        ]);

        if (! is_array($result)
            || ! is_int($result['status'] ?? null)
            || $result['status'] < 0
            || $result['status'] > 65_535
            || ! array_key_exists('headers', $result)
            || ! is_array($result['headers'])
            || ! array_is_list($result['headers'])
            || ! array_key_exists('body', $result)
            || ! is_array($result['body'])
            || ! array_is_list($result['body'])
            || array_key_exists('resource', $result)) {
            throw new InvalidPluginResultException('HTTP capability returned an invalid response.');
        }

        $responseHeaders = [];

        foreach ($result['headers'] as $header) {
            if (! is_array($header) || ! is_string($header['name'] ?? null) || ! is_string($header['value'] ?? null)) {
                throw new InvalidPluginResultException('HTTP capability returned an invalid header.');
            }

            $responseHeaders[$header['name']] = $header['value'];
        }

        $bodyBytes = '';

        foreach ($result['body'] as $byte) {
            if (! is_int($byte) || $byte < 0 || $byte > 255) {
                throw new InvalidPluginResultException('HTTP capability returned an invalid body byte.');
            }

            $bodyBytes .= chr($byte);
        }

        return new HttpResponse(
            $result['status'],
            $responseHeaders,
            $bodyBytes,
        );
    }
}
