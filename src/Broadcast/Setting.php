<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One plugin-defined destination or operation setting.
 */
final readonly class Setting
{
    /**
     * Plugin-defined key, preserved without normalization.
     */
    public string $key;

    /**
     * Text, boolean, or signed integer value supplied by the host.
     */
    public string|bool|int $value;

    /**
     * Keep the setting's key and typed value together.
     */
    public function __construct(string $key, string|bool|int $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
