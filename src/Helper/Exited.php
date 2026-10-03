<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * The helper exited normally. The exit code may still be non-zero.
 */
final readonly class Exited
{
    /**
     * Exit code returned by the helper.
     */
    public int $code;

    /**
     * Staged stdout returned after a normal exit, if one was used.
     */
    public ?Writer $output;

    /**
     * Describe a normal helper exit.
     */
    public function __construct(int $code, ?Writer $output)
    {
        $this->code = $code;
        $this->output = $output;
    }
}
