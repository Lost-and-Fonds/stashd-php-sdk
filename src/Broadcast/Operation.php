<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

/**
 * A request to run one named Broadcast operation.
 */
final readonly class Operation
{
    /**
     * Plugin-defined operation name.
     */
    public string $name;

    /**
     * Current destination settings.
     * @var list<Setting>
     */
    public array $settings;

    /**
     * Values supplied specifically for this operation.
     * @var list<Setting>
     */
    public array $payload;

    /**
     * Create an operation request.
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
