<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Invocation-scoped input-host.credential-access capability.
 * Explicit release ends ownership; retained objects cannot extend invocation authority.
 */
interface CredentialAccess
{
    /**
     * Explicitly release ownership; duplicate release and later use violate resource lifetime.
     */
    public function close(): void;

    /**
     * Invoke canonical credential-access.read on this live resource.
     * Ordinary host failures are distinct from protocol violations.
     * @return string
     */
    public function read(): string;

}
