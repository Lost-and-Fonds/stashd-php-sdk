<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use RuntimeException;

/**
 * An ordinary publication or destination-action failure returned to Core.
 */
final class Failure extends RuntimeException
{
    /**
     * Describe a failed destination operation without exposing credentials.
     * @param string $message Explanation suitable for the caller.
     * @param bool $retryable Whether retrying this operation may succeed.
     */
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }
}
