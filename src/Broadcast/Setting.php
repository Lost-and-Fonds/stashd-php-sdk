<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One named value used by a Broadcast destination or operation.
 */
final readonly class Setting
{
    /**
     * Create a setting from its key and typed value.
     * @param string $key The plugin-defined setting name.
     * @param string|bool|int $value The selected configuration value.
     */
    public function __construct(public string $key, public string|bool|int $value) {}
}
