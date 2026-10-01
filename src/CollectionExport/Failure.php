<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * Ordinary typed export outcome, never a substitute for malformed protocol or contract values.
 */
final readonly class Failure
{
    /**
     * Canonical plugin-authored error category.
     */
    public ErrorKind $kind;

    /**
     * Human-readable plugin diagnostic, not automatically safe for tracing.
     */
    public string $message;

    /**
     * Whether retrying this failed invocation may succeed.
     */
    public bool $retryable;

    /**
     * Return a coherent execution failure without converting protocol violations into success-shaped data.
     */
    public function __construct(ErrorKind $kind, string $message, bool $retryable = false)
    {
        $this->kind = $kind;
        $this->message = $message;
        $this->retryable = $retryable;
    }
}
