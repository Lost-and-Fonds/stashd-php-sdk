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
     * Keep the item, caller settings and tools together for acquisition.
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
     * Create temporary output to save or hand to a helper.
     */

    /**
     * Return tools available while saving the item.
     */

    /**
     * Return credential selectors granted for this acquisition.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->helpers->credentials();
    }
}
