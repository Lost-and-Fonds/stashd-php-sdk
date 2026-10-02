<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\ProgressHost;

/**
 * Immutable progress-host.progress contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class Progress
{
    /**
     * Canonical stage value; retained in contract order without normalization.
     * @var string
     */
    public string $stage;

    /**
     * Canonical fraction value; retained in contract order without normalization.
     * @var float|null
     */
    public ?float $fraction;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $stage
     * @param float|null $fraction
     */
    public function __construct(
        string $stage,
        ?float $fraction,
    ) {
        $this->stage = $stage;
        $this->fraction = $fraction;
    }
}
