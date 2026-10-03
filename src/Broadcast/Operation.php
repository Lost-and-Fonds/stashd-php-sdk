<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * One host-requested plugin operation with its settings and caller input intact.
 */
final readonly class Operation
{
    /**
     * Stable plugin-defined operation name.
     */
    public string $name;

    /**
     * Destination settings in their original order.
     * @var list<Setting>
     */
    public array $settings;

    /**
     * Operation-specific caller input in its original order.
     * @var list<Setting>
     */
    public array $payload;

    /**
     * Preserve the operation's values without interpreting provider semantics.
     * @param list<Setting> $settings
     * @param list<Setting> $payload
     */
    public function __construct(string $name, array $settings, array $payload)
    {
        $this->name = $name;
        $this->settings = $settings;
        $this->payload = $payload;
    }
}
