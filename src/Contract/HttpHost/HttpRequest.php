<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

use Stashd\PluginSdk\Contract\IoHost\ByteStream;
use Stashd\PluginSdk\Contract\IoHost\CredentialReference;

/**
 * Immutable http-host.http-request contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class HttpRequest
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $method
     * @param string $url
     * @param CredentialReference|null $credential
     * @param list<HttpHeader> $headers
     * @param ByteStream|null $body
     */
    public function __construct(
        public string $method,
        public string $url,
        public ?CredentialReference $credential,
        public array $headers,
        public ?ByteStream $body,
    ) {}
}
