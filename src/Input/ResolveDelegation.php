<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;

/**
 * A handoff from another Input to resolve an opaque reference.
 */
final readonly class ResolveDelegation
{
    /**
     * Create a request to resolve a handoff from another Input plugin.
     *
     * @param Helpers $helpers Tools and credentials available for this handoff.
     */
    public function __construct(
        /**
         * Delegated opaque reference.
         */
        public string $reference,
        private Helpers $helpers,
    ) {}

    /**
     * Return credential selectors supplied for this handoff.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }
}
