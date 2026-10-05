<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * The partial form of discovery finish.
 */
final readonly class DiscoveryFinishPartial implements DiscoveryFinish
{
    /**
     * Data carried by this result.
     * @var list<Deficiency>
     */
    public array $value;

    /**
     * Create this result with its associated data.
     * @param list<Deficiency> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
