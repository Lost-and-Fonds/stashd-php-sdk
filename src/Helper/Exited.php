<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * Normal child exit; a nonzero exit code remains a normal exit, not a host failure.
 */
final readonly class Exited
{
    /**
     * Signed child exit code.
     */
    public int $code;

    /**
     * Writer returned only when staged stdout remained valid.
     */
    public ?Writer $output;

    /**
     * Keep the terminal exit code and returned writer together.
     */
    public function __construct(int $code, ?Writer $output)
    {
        $this->code = $code;
        $this->output = $output;
    }
}
