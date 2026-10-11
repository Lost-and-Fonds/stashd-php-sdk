<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use RuntimeException;

/**
 * An ordinary Input failure, distinct from a broken plugin or connection.
 */
final class Failure extends RuntimeException
{
    /**
     * Explain why the operation failed without including secrets.
     * @param ErrorKind $kind Category of failure.
     * @param string $message Safe explanation for the caller.
     * @param bool $retryable Whether another attempt may succeed.
     */
    public function __construct(public readonly ErrorKind $kind, string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}
