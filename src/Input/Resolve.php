<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;

/**
 * A request to identify a source before discovering its items.
 */
final readonly class Resolve
{
    /**
     * Create a request to identify a source.
     *
     * @param Source $source Source values supplied by the caller.
     * @param Helpers $helpers Tools and credentials available during this call.
     */
    public function __construct(
        public Source $source,
        private Helpers $helpers,
    ) {}

    /**
     * Return the source values supplied by the caller.
     */
    public function source(): Source
    {
        return $this->source;
    }

    /**
     * Return credential selectors available for source resolution.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }
}
