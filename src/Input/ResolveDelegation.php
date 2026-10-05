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
     * Keep the delegated reference and available helpers together.
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
