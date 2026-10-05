<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Normal child exit, including non-zero codes, optionally returning the still-valid owned staged writer.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class HelperExit
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param int $code
     * @param StagedWriter|null $output
     */
    public function __construct(
        public int $code,
        public ?StagedWriter $output,
    ) {}
}
