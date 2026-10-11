<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * The event form of helper poll state.
 */
final readonly class HelperPollStateEvent implements HelperPollState
{
    /**
     * Data carried by this result.
     * @var HelperEvent
     */
    public HelperEvent $value;

    /**
     * Create this result with its associated data.
     * @param HelperEvent $value
     */
    public function __construct(HelperEvent $value)
    {
        $this->value = $value;
    }
}
