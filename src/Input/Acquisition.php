<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;

/**
 * All information needed to save one discovered item independently.
 */
final readonly class Acquisition
{
    /**
     * Create a request to save one discovered item.
     *
     * @param Helpers $helpers Tools and credentials available while saving this item.
     */
    public function __construct(
        /**
         * Item supplied for this acquisition.
         */
        public DiscoveredItem $item,
        /**
         * Selected options, with original order and types intact.
         */
        public Source $options,
        private Helpers $helpers,
    ) {}

    /**
     * Return credential selectors granted for this acquisition.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }
}
