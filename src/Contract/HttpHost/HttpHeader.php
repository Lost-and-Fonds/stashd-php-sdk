<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * One HTTP header name and value.
 */
final readonly class HttpHeader
{
    /**
     * Create the http header.
     *
     * @param string $name Name used to select this entry.
     * @param string $value Value associated with this entry.
     */
    public function __construct(
        public string $name,
        public string $value,
    ) {}
}
