<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * Host-granted credential selector; its opaque reference is not a secret value.
 */
final readonly class Credential
{
    /**
     * Helper environment variable receiving the host-mediated credential.
     */
    public string $name;

    /**
     * Host-granted opaque selector, not a filesystem or Vault path.
     */
    public string $reference;

    /**
     * Bind one approved credential slot to its selector.
     */
    public function __construct(string $name, string $reference)
    {
        $this->name = $name;
        $this->reference = $reference;
    }
}
