<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Immutable broadcast-plugin.operation-result contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class OperationResult
{
    /**
     * Canonical choices value; retained in contract order without normalization.
     * @var list<Choice>
     */
    public array $choices;

    /**
     * Canonical values value; retained in contract order without normalization.
     * @var list<Setting>
     */
    public array $values;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<Choice> $choices
     * @param list<Setting> $values
     */
    public function __construct(
        array $choices,
        array $values,
    ) {
        $this->choices = $choices;
        $this->values = $values;
    }
}
