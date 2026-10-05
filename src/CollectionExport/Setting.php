<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * A named export setting; never use it to store secrets.
 */
final readonly class Setting
{
    /**
     * Create a setting without converting numeric text to an integer.
     *
     * @param string $key Plugin-defined setting name; duplicate names are allowed.
     * @param bool|int|string $value Boolean, signed integer or text.
     */
    public function __construct(public string $key, public bool|int|string $value) {}
}
