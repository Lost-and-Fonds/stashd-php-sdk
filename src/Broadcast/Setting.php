<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One named value used by a Broadcast destination or operation.
 */
final readonly class Setting
{
    /**
     * Plugin-defined setting name.
     */
    public string $key;

    /**
     * Setting value.
     */
    public string|bool|int $value;

    /**
     * Create a setting.
     */
    public function __construct(string $key, string|bool|int $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
