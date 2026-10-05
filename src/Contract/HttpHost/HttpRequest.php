<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

use Stashd\PluginSdk\Contract\IoHost\ByteStream;
use Stashd\PluginSdk\Contract\IoHost\CredentialReference;

/**
 * An HTTP request; sending it transfers ownership of its body stream.
 */
final readonly class HttpRequest
{
    /**
     * Create the http request.
     *
     * @param string $method Case-sensitive HTTP method token.
     * @param string $url Address to request.
     * @param CredentialReference|null $credential Credential to use; authorization and availability are checked for each request.
     * @param list<HttpHeader> $headers HTTP headers in order, including repeated names.
     * @param ByteStream|null $body Request body stream transferred to the host; null when there is no body.
     */
    public function __construct(
        public string $method,
        public string $url,
        public ?CredentialReference $credential,
        public array $headers,
        public ?ByteStream $body,
    ) {}
}
