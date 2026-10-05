<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\ProgressHost;

/**
 * A work stage and optional completion fraction between zero and one.
 */
final readonly class Progress
{
    /**
     * Create the progress.
     *
     * @param string $stage Description of the work currently in progress.
     * @param float|null $fraction Completion fraction from zero to one; null for unknown progress.
     */
    public function __construct(
        public string $stage,
        public ?float $fraction,
    ) {}
}
