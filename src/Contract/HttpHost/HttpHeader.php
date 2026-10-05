<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * Immutable http-host.http-header contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class HttpHeader
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $name
     * @param string $value
     */
    public function __construct(
        public string $name,
        public string $value,
    ) {}
}
