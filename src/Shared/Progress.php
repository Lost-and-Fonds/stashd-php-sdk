<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * A description of current work and its completion fraction, when known.
 */
final readonly class Progress
{
    /**
     * Create a progress report; fractions outside zero to one are rejected.
     */
    public function __construct(
        /**
         * Description of the work currently in progress.
         */
        public string $stage,
        /**
         * Completion fraction from zero to one; null when unknown.
         */
        public ?float $fraction = null,
    ) {
        if ($fraction !== null && (!is_finite($fraction) || $fraction < 0 || $fraction > 1)) {
            throw new ProtocolViolation('Progress fraction must be finite and within [0, 1]');
        }

    }
}
