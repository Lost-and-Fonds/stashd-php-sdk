<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\CollectionExport;

/**
 * An export that failed, with an explanation for the caller.
 */
final readonly class Failure
{
    /**
     * Category of the export error.
     */
    public ErrorKind $kind;

    /**
     * Explanation for the caller; do not include secrets.
     */
    public string $message;

    /**
     * Whether retrying the export may succeed.
     */
    public bool $retryable;

    /**
     * Describe an export failure and whether retrying may help.
     */
    public function __construct(ErrorKind $kind, string $message, bool $retryable = false)
    {
        $this->kind = $kind;
        $this->message = $message;
        $this->retryable = $retryable;
    }
}
