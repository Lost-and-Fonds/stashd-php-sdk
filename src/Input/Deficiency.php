<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use InvalidArgumentException;

/**
 * A reason why finding or saving items was incomplete.
 */
final readonly class Deficiency
{
    /**
     * Explain an incomplete result and whether retry may help.
     * @param string $disposition Whether the gap is retryable, final, or unknown.
     * @param Diagnostic $diagnostic Explanation and supporting evidence.
     */
    public function __construct(public string $disposition, public Diagnostic $diagnostic)
    {
        if (!in_array($disposition, ['retryable', 'terminal', 'unknown'], true)) {
            throw new InvalidArgumentException('Unknown deficiency disposition');
        }
    }
}
