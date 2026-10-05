<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

/**
 * The unavailable form of collection read error.
 */
final readonly class CollectionReadErrorUnavailable implements CollectionReadError
{
    /**
     * Data carried by this result.
     * @var string
     */
    public string $value;

    /**
     * Create this result with its associated data.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
