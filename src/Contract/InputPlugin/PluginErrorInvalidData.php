<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\PluginTypes\PluginErrorDetail;

/**
 * Canonical invalid-data branch of input-plugin.plugin-error.
 */
final readonly class PluginErrorInvalidData implements PluginError
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var PluginErrorDetail
     */
    public PluginErrorDetail $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param PluginErrorDetail $value
     */
    public function __construct(PluginErrorDetail $value)
    {
        $this->value = $value;
    }
}
