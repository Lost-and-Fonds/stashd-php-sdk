<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Canonical finished branch of input-host.discovery-progress.
 */
final readonly class DiscoveryProgressFinished implements DiscoveryProgress
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var DiscoveryFinish
     */
    public DiscoveryFinish $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param DiscoveryFinish $value
     */
    public function __construct(DiscoveryFinish $value)
    {
        $this->value = $value;
    }
}
