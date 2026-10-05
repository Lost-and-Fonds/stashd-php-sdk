<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * The unavailable form of http error.
 */
final readonly class HttpErrorUnavailable implements HttpError
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
