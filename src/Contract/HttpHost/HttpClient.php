<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * A http client available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface HttpClient
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run request on this http client.
     * Ordinary host failures are distinct from protocol violations.
     * @param HttpRequest $request
     * @return HttpResponse
     */
    public function request(HttpRequest $request): HttpResponse;

}
