<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use InvalidArgumentException;

/**
 * How much of the requested discovery run was covered.
 */
final readonly class DiscoveryFinish
{
    /**
     * Keep the declared finish outcome and its supporting details together.
     * @param string $kind Exhaustive, partial, or indeterminate coverage.
     * @param string|null $refreshState Baseline to use for a later successful refresh.
     * @param list<Deficiency> $deficiencies Reasons the run was incomplete.
     * @param Diagnostic|null $diagnostic Explanation when coverage is indeterminate.
     */
    private function __construct(
        public string $kind,
        public ?string $refreshState = null,
        public array $deficiencies = [],
        public ?Diagnostic $diagnostic = null,
    ) {}

    /**
     * Mark the run exhaustive and optionally save a baseline for next time.
     */
    public static function exhaustive(?string $refreshState = null): self
    {
        return new self('exhaustive', $refreshState);
    }

    /**
     * Mark coherent but incomplete coverage with at least one reason.
     * @param list<Deficiency> $deficiencies
     */
    public static function partial(array $deficiencies): self
    {
        if ($deficiencies === []) {
            throw new InvalidArgumentException('Partial discovery needs at least one deficiency');
        }

        return new self('partial', deficiencies: $deficiencies);
    }

    /**
     * Mark coverage uncertain and explain why.
     */
    public static function indeterminate(Diagnostic $diagnostic): self
    {
        return new self('indeterminate', diagnostic: $diagnostic);
    }
}
