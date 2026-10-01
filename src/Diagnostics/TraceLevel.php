<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Diagnostics;

/**
 * Ordered diagnostic verbosity; disabled tracing never serializes event context.
 */
enum TraceLevel: int
{
    case Off = 0;
    case Basic = 1;
    case Verbose = 2;
    case Wire = 3;
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
