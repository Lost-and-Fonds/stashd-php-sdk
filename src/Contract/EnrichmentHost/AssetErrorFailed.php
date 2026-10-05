<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\EnrichmentHost;

/**
 * The failed form of asset error.
 */
final readonly class AssetErrorFailed implements AssetError
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
