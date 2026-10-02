<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\PluginTypes;

/**
 * Immutable plugin-types.plugin-error-detail contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class PluginErrorDetail
{
    /**
     * Canonical message value; retained in contract order without normalization.
     * @var string
     */
    public string $message;

    /**
     * Canonical retryable value; retained in contract order without normalization.
     * @var bool
     */
    public bool $retryable;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $message
     * @param bool $retryable
     */
    public function __construct(
        string $message,
        bool $retryable,
    ) {
        $this->message = $message;
        $this->retryable = $retryable;
    }
}
