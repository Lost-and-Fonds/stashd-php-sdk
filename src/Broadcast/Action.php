<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;

/**
 * A named interactive destination action, separate from publishing items.
 */
final readonly class Action
{
    /**
     * Keep the action and its caller-supplied settings together.
     * @param string $name Plugin-defined destination action.
     * @param list<Setting> $settings Current destination configuration.
     * @param list<Setting> $payload Values supplied specifically for the action.
     */
    public function __construct(
        public string $name,
        public array $settings,
        public array $payload,
        private Helpers $helpers,
    ) {}

    /**
     * Return tools available while running this action.
     */
    public function helpers(): Helpers
    {
        return $this->helpers;
    }

    /**
     * Return credential selectors granted to this action.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }
}
