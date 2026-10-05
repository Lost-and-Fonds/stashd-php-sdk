<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * The more form of discovery progress.
 */
final readonly class DiscoveryProgressMore implements DiscoveryProgress
{
    /**
     * Data carried by this result.
     * @var DiscoveryContinuation
     */
    public DiscoveryContinuation $value;

    /**
     * Create this result with its associated data.
     * @param DiscoveryContinuation $value
     */
    public function __construct(DiscoveryContinuation $value)
    {
        $this->value = $value;
    }
}
