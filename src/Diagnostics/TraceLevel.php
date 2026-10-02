<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Diagnostics;

/**
 * Ordered diagnostic verbosity; disabled tracing never serializes event context.
 */
enum TraceLevel: int
{
    /**
     * Disable SDK diagnostic output.
     */
    case Off = 0;
    /**
     * Record major invocation and failure events.
     */
    case Basic = 1;
    /**
     * Include additional resource and execution detail.
     */
    case Verbose = 2;
    /**
     * Include safe RPC frame metadata without secrets.
     */
    case Wire = 3;
    /**
     * Record all available safe runtime detail.
     */
    case Ludicrous = 4;

    /**
     * Resolve the documented environment setting without enabling diagnostics by default.
     */
    public static function fromEnvironment(): self
    {
        return match (getenv('STASHD_PHP_SDK_TRACE')) {
            'basic' => self::Basic,
            'verbose' => self::Verbose,
            'wire' => self::Wire,
            'ludicrous' => self::Ludicrous,
            default => self::Off,
        };
    }
}
