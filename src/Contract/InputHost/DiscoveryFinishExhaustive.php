<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Canonical exhaustive branch of input-host.discovery-finish.
 */
final readonly class DiscoveryFinishExhaustive implements DiscoveryFinish
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var DiscoveryRefreshState|null
     */
    public ?DiscoveryRefreshState $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param DiscoveryRefreshState|null $value
     */
    public function __construct(?DiscoveryRefreshState $value)
    {
        $this->value = $value;
    }
}
