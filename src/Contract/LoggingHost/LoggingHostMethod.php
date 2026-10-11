<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\LoggingHost;

/**
 * RPC operations declared by the logging-host interface.
 */
enum LoggingHostMethod: string
{
    /**
     * Identifies the log wire operation.
     */
    case Log = 'stashd:plugin/logging-host.log';
}
