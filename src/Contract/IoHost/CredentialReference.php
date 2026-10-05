<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * An opaque credential selector; knowing its ID does not grant access.
 */
final readonly class CredentialReference
{
    /**
     * Create the credential reference.
     *
     * @param string $id Opaque credential selector; the host checks permission each time it is used.
     */
    public function __construct(
        public string $id,
    ) {}
}
