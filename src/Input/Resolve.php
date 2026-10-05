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
     * Set up resolution with caller values and the available helpers.
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
