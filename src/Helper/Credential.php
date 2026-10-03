<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * A credential that this plugin call may pass to a helper.
 */
final readonly class Credential
{
    /**
     * Environment variable name the helper will receive.
     */
    public string $name;

    /**
     * Opaque host reference used to select the credential. It is not the secret.
     */
    public string $reference;

    /**
     * Create a helper credential selector.
     */
    public function __construct(string $name, string $reference)
    {
        $this->name = $name;
        $this->reference = $reference;
    }
}
