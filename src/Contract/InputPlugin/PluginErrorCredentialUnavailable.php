<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\PluginTypes\PluginErrorDetail;

/**
 * The credential unavailable form of plugin error.
 */
final readonly class PluginErrorCredentialUnavailable implements PluginError
{
    /**
     * Data carried by this result.
     * @var PluginErrorDetail
     */
    public PluginErrorDetail $value;

    /**
     * Create this result with its associated data.
     * @param PluginErrorDetail $value
     */
    public function __construct(PluginErrorDetail $value)
    {
        $this->value = $value;
    }
}
