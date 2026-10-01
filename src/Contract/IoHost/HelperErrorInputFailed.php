<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Canonical input-failed branch of io-host.helper-error.
 */
final readonly class HelperErrorInputFailed implements HelperError
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var string
     */
    public string $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
