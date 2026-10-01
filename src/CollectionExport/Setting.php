<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Non-secret exporter configuration; PHP integer values map exactly to canonical signed-64 strings.
 */
final readonly class Setting
{
    /**
     * Opaque plugin-defined option key; duplicate interpretation is not invented by the SDK.
     */
    public string $key;

    /**
     * Boolean, signed integer or text, corresponding to the three canonical option cases.
     */
    public bool|int|string $value;

    /**
     * Retain configuration types so textual numbers remain text rather than numeric options.
     */
    public function __construct(string $key, bool|int|string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
