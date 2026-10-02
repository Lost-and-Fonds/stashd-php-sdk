<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

/**
 * Internal proxy identity used to transfer an owned host resource without string pseudo-handles.
 */
interface OwnedResource
{
    /**
     * Return the invocation-scoped host identity after checking the proxy belongs to this invocation.
     */
    public function resourceId(ResourceTable $table, string $invocation, string $type): string;
}
