<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * RPC operations declared by the http-host interface.
 */
enum HttpHostMethod: string
{
    /**
     * Identifies the open http client wire operation.
     */
    case OpenHttpClient = 'stashd:plugin/http-host.open-http-client';
    /**
     * Identifies the http client.request wire operation.
     */
    case HttpClientRequest = 'stashd:plugin/http-host.http-client.request';
}
