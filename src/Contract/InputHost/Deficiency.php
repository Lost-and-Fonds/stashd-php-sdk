<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * A known gap in the saved result and whether retrying may fill it.
 */
final readonly class Deficiency
{
    /**
     * Create the deficiency.
     *
     * @param DeficiencyDisposition $disposition Whether later work may fill this gap.
     * @param OutcomeDiagnostic $diagnostic Explanation of the missing work.
     */
    public function __construct(
        public DeficiencyDisposition $disposition,
        public OutcomeDiagnostic $diagnostic,
    ) {}
}
