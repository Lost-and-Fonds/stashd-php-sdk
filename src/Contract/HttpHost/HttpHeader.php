<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\HttpHost;

/**
 * Immutable http-host.http-header contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class HttpHeader
{
    /**
     * Canonical name value; retained in contract order without normalization.
     * @var string
     */
    public string $name;

    /**
     * Canonical value value; retained in contract order without normalization.
     * @var string
     */
    public string $value;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $name
     * @param string $value
     */
    public function __construct(
        string $name,
        string $value,
    ) {
        $this->name = $name;
        $this->value = $value;
    }
}
