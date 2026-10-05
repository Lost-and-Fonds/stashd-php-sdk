<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Non-secret exporter configuration; PHP integer values map exactly to canonical signed-64 strings.
 */
final readonly class Setting
{
    /**
     * Retain configuration types so textual numbers remain text rather than numeric options.
     *
     * @param string $key Opaque plugin-defined key; duplicate keys are preserved.
     * @param bool|int|string $value Boolean, signed integer or text.
     */
    public function __construct(public string $key, public bool|int|string $value) {}
}
