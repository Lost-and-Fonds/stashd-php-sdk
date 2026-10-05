<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

/**
 * One caller-supplied Input option, retaining its original key and type.
 */
final readonly class Option
{
    /**
     * Keep a typed Input option for resolution, discovery or acquisition.
     * @param string $key Plugin-defined option key.
     * @param bool|int|string $value Boolean, signed number or text option.
     */
    public function __construct(public string $key, public bool|int|string $value) {}
}
