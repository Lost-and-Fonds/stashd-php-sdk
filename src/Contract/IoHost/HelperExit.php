<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Normal child exit, including non-zero codes, optionally returning the still-valid owned staged writer.
 */
final readonly class HelperExit
{
    /**
     * Create the helper exit.
     *
     * @param int $code Signed 32-bit normal child exit code; non-zero does not mean host runtime failure.
     * @param StagedWriter|null $output Owned staged stdout writer returned only after normal exit so the plugin can finish it.
     */
    public function __construct(
        public int $code,
        public ?StagedWriter $output,
    ) {}
}
