<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Immutable io-host.helper-result contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class HelperResult
{
    /**
     * Canonical exit-code value; retained in contract order without normalization.
     * @var int
     */
    public int $exitCode;

    /**
     * Canonical stdout value; retained in contract order without normalization.
     * @var string
     */
    public string $stdout;

    /**
     * Canonical stderr value; retained in contract order without normalization.
     * @var string
     */
    public string $stderr;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param int $exitCode
     * @param string $stdout
     * @param string $stderr
     */
    public function __construct(
        int $exitCode,
        string $stdout,
        string $stderr,
    ) {
        $this->exitCode = $exitCode;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }
}
