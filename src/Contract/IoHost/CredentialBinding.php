<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A named credential available during the current call.
 */
final readonly class CredentialBinding
{
    /**
     * Create the credential binding.
     *
     * @param string $name Plugin-defined slot name; used as the environment variable name for helpers.
     * @param CredentialReference $reference Authorized credential available for this call.
     */
    public function __construct(
        public string $name,
        public CredentialReference $reference,
    ) {}
}
