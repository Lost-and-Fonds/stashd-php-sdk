<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Canonical more branch of input-host.discovery-progress.
 */
final readonly class DiscoveryProgressMore implements DiscoveryProgress
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var DiscoveryContinuation
     */
    public DiscoveryContinuation $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param DiscoveryContinuation $value
     */
    public function __construct(DiscoveryContinuation $value)
    {
        $this->value = $value;
    }
}
