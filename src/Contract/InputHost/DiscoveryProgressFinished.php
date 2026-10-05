<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * The finished form of discovery progress.
 */
final readonly class DiscoveryProgressFinished implements DiscoveryProgress
{
    /**
     * Data carried by this result.
     * @var DiscoveryFinish
     */
    public DiscoveryFinish $value;

    /**
     * Create this result with its associated data.
     * @param DiscoveryFinish $value
     */
    public function __construct(DiscoveryFinish $value)
    {
        $this->value = $value;
    }
}
