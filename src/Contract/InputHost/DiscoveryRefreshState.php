<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * A baseline shared between successfully completed discovery runs.
 */
final readonly class DiscoveryRefreshState
{
    /**
     * Create the discovery refresh state.
     *
     * @param string $value Plugin-owned baseline from a successfully completed run.
     */
    public function __construct(
        public string $value,
    ) {}
}
