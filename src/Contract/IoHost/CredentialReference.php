<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.credential-reference contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class CredentialReference
{
    /**
     * Canonical id value; retained in contract order without normalization.
     * @var string
     */
    public string $id;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $id
     */
    public function __construct(
        string $id,
    ) {
        $this->id = $id;
    }
}
