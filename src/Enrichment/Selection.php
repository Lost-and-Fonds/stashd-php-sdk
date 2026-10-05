<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

/**
 * A caller-selected value for one advertised capability setting.
 */
final readonly class Selection
{
    /**
     * Select exactly one value for the given key.
     * @param string $key Key advertised by the capability.
     * @param string $value Caller-selected option value.
     */
    public function __construct(public string $key, public string $value) {}
}
