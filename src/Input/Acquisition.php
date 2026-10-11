<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Tools;

/**
 * All information needed to save one discovered item independently.
 */
final readonly class Acquisition
{
    /**
     * Create a request to save one discovered item.
     *
     * @param DiscoveredItem $item Item selected for acquisition.
     * @param list<Option> $options Caller-selected settings.
     * @param Tools $tools Tools and credentials available while saving this item.
     */
    public function __construct(
        public DiscoveredItem $item,
        public array $options,
        private Tools $tools,
    ) {}

    /**
     * Return credential selectors granted for this acquisition.
     */
    public function tools(): Tools
    {
        return $this->tools;
    }

    /**
     * Return credentials available during this call.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->tools->credentials;
    }
}
