<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * The helper exited normally. The exit code may still be non-zero.
 */
final readonly class Exited
{
    /**
     * Describe a normal helper exit.
     * @param int $code Exit code returned by the helper.
     * @param Writer|null $output Staged stdout returned after a normal exit, if one was used.
     */
    public function __construct(public int $code, public ?Writer $output) {}
}
