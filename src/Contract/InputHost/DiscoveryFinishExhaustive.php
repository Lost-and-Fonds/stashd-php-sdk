<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * The exhaustive form of discovery finish.
 */
final readonly class DiscoveryFinishExhaustive implements DiscoveryFinish
{
    /**
     * Data carried by this result.
     * @var DiscoveryRefreshState|null
     */
    public ?DiscoveryRefreshState $value;

    /**
     * Create this result with its associated data.
     * @param DiscoveryRefreshState|null $value
     */
    public function __construct(?DiscoveryRefreshState $value)
    {
        $this->value = $value;
    }
}
