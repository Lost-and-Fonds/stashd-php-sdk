<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.credential-binding contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class CredentialBinding
{
    /**
     * Canonical name value; retained in contract order without normalization.
     * @var string
     */
    public string $name;

    /**
     * Canonical reference value; retained in contract order without normalization.
     * @var CredentialReference
     */
    public CredentialReference $reference;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $name
     * @param CredentialReference $reference
     */
    public function __construct(
        string $name,
        CredentialReference $reference,
    ) {
        $this->name = $name;
        $this->reference = $reference;
    }
}
