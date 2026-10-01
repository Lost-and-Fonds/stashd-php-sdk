<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

/**
 * Immutable input-plugin.input-option contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class InputOption
{
    /**
     * Canonical key value; retained in contract order without normalization.
     * @var string
     */
    public string $key;

    /**
     * Canonical value value; retained in contract order without normalization.
     * @var OptionValue
     */
    public OptionValue $value;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $key
     * @param OptionValue $value
     */
    public function __construct(
        string $key,
        OptionValue $value,
    ) {
        $this->key = $key;
        $this->value = $value;
    }
}
