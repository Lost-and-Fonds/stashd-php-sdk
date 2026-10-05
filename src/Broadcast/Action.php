<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;

/**
 * Values supplied for one named destination action.
 */
final readonly class Action
{
    /**
     * Create a request to run a destination action.
     *
     * @param string $name Plugin-defined action name.
     * @param Helpers $tools Tools and credentials available for this action.
     * @param list<Setting> $settings Current destination configuration.
     * @param list<Setting> $payload Values supplied specifically for this action.
     */
    public function __construct(
        public string $name,
        public array $settings,
        public array $payload,
        private Helpers $tools,
    ) {}

    /**
     * Return credential selectors granted for this action.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->tools->credentials();
    }

    /**
     * Create temporary output or run an allowed helper.
     */
    public function tools(): Helpers
    {
        return $this->tools;
    }
}
