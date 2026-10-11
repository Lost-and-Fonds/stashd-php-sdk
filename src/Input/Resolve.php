<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Tools;

/**
 * A request to identify a source before discovering its items.
 */
final readonly class Resolve
{
    /**
     * Create a request to identify a source.
     *
     * @param Source $source Source values supplied by the caller.
     * @param Tools $tools Tools and credentials available during this call.
     */
    public function __construct(
        public Source $source,
        private Tools $tools,
    ) {}

    /**
     * Return helper and staging tools available during resolution.
     */
    public function tools(): Tools
    {
        return $this->tools;
    }

    /**
     * Return credential selectors available for source resolution.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->tools->credentials;
    }
}
