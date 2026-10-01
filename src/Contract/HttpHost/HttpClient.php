<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * Invocation-scoped http-host.http-client capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface HttpClient
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical http-client.request on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @param HttpRequest $request
     * @return HttpResponse
     */
    public function request(HttpRequest $request): HttpResponse;

}
