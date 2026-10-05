<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\PluginTypes\PluginErrorDetail;

/**
 * The rate limited form of plugin error.
 */
final readonly class PluginErrorRateLimited implements PluginError
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
