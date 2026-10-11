<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\ProgressHost;

/**
 * RPC operations declared by the progress-host interface.
 */
enum ProgressHostMethod: string
{
    /**
     * Identifies the report progress wire operation.
     */
    case ReportProgress = 'stashd:plugin/progress-host.report-progress';
}
