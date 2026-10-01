<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Stage-local fractional completion; null is indeterminate and stages need not be monotonic.
 */
final readonly class Progress
{
    /**
     * Plugin-defined presentation text, never parsed as a lifecycle state.
     */
    public string $stage;

    /**
     * Finite fraction in the inclusive unit interval, or null for stage-only reporting.
     */
    public ?float $fraction;

    /**
     * Reject invalid fractions rather than clamping or reinterpreting percentages.
     */
    public function __construct(string $stage, ?float $fraction = null)
    {
        if ($fraction !== null && (!is_finite($fraction) || $fraction < 0 || $fraction > 1)) {
            throw new ProtocolViolation('Progress fraction must be finite and within [0, 1]');
        }

        $this->stage = $stage;
        $this->fraction = $fraction;
    }
}
