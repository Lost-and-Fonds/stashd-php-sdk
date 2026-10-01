<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

use Stashd\PluginSdk\Contract\IoHost\ByteStream;

/**
 * Immutable http-host.http-response contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class HttpResponse
{
    /**
     * Canonical status value; retained in contract order without normalization.
     * @var int
     */
    public int $status;

    /**
     * Canonical headers value; retained in contract order without normalization.
     * @var list<HttpHeader>
     */
    public array $headers;

    /**
     * Canonical body value; retained in contract order without normalization.
     * @var ByteStream
     */
    public ByteStream $body;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param int $status
     * @param list<HttpHeader> $headers
     * @param ByteStream $body
     */
    public function __construct(
        int $status,
        array $headers,
        ByteStream $body,
    ) {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }
}
