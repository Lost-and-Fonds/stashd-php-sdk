<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Immutable input-host.input-delegation contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class InputDelegation
{
    /**
     * Canonical reference value; retained in contract order without normalization.
     * @var string
     */
    public string $reference;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $reference
     */
    public function __construct(
        string $reference,
    ) {
        $this->reference = $reference;
    }
}
