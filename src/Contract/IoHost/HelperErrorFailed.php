<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * The failed form of helper error.
 */
final readonly class HelperErrorFailed implements HelperError
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
