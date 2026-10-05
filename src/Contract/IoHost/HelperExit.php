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
     * Signed 32-bit normal child exit code; non-zero does not mean host runtime failure.
     * @var int
     */
    public int $code;

    /**
     * Owned staged stdout writer returned only after normal exit so the plugin can finish it.
     * @var StagedWriter|null
     */
    public ?StagedWriter $output;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param int $code
     * @param StagedWriter|null $output
     */
    public function __construct(
        int $code,
        ?StagedWriter $output,
    ) {
        $this->code = $code;
        $this->output = $output;
    }
}
