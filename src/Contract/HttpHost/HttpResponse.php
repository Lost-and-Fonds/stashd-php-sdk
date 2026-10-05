<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

use Stashd\PluginSdk\Contract\IoHost\ByteStream;

/**
 * Immutable http-host.http-response contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class HttpResponse
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param int $status
     * @param list<HttpHeader> $headers
     * @param ByteStream $body
     */
    public function __construct(
        public int $status,
        public array $headers,
        public ByteStream $body,
    ) {}
}
