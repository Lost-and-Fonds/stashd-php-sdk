<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * A credential access available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface CredentialAccess
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run read on this credential access.
     * Ordinary host failures are distinct from protocol violations.
     * @return string
     */
    public function read(): string;

}
