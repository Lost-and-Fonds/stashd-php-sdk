<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.credential-binding contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class CredentialBinding
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $name
     * @param CredentialReference $reference
     */
    public function __construct(
        public string $name,
        public CredentialReference $reference,
    ) {}
}
