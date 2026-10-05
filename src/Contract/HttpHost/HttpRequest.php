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
     * Canonical method value; retained in contract order without normalization.
     * @var string
     */
    public string $method;

    /**
     * Canonical url value; retained in contract order without normalization.
     * @var string
     */
    public string $url;

    /**
     * Canonical credential value; retained in contract order without normalization.
     * @var CredentialReference|null
     */
    public ?CredentialReference $credential;

    /**
     * Canonical headers value; retained in contract order without normalization.
     * @var list<HttpHeader>
     */
    public array $headers;

    /**
     * Canonical body value; retained in contract order without normalization.
     * @var ByteStream|null
     */
    public ?ByteStream $body;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $method
     * @param string $url
     * @param CredentialReference|null $credential
     * @param list<HttpHeader> $headers
     * @param ByteStream|null $body
     */
    public function __construct(
        string $method,
        string $url,
        ?CredentialReference $credential,
        array $headers,
        ?ByteStream $body,
    ) {
        $this->method = $method;
        $this->url = $url;
        $this->credential = $credential;
        $this->headers = $headers;
        $this->body = $body;
    }
}
