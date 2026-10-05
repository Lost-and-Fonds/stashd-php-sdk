<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

use Stashd\PluginSdk\Contract\IoHost\ByteStream;

/**
 * An HTTP response with a readable body stream.
 */
final readonly class HttpResponse
{
    /**
     * Create the http response.
     *
     * @param int $status HTTP response status code.
     * @param list<HttpHeader> $headers HTTP headers in order, including repeated names.
     * @param ByteStream $body Stream containing the body bytes.
     */
    public function __construct(
        public int $status,
        public array $headers,
        public ByteStream $body,
    ) {}
}
