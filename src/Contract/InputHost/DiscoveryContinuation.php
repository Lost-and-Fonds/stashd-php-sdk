<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * A restart point for an unfinished discovery run, with no credentials.
 */
final readonly class DiscoveryContinuation
{
    /**
     * Create the discovery continuation.
     *
     * @param string $value Plugin-owned restart data that works across processes and contains no credentials.
     */
    public function __construct(
        public string $value,
    ) {}
}
