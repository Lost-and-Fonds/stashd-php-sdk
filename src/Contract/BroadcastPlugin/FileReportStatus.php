<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Available file report status values.
 */
enum FileReportStatus: string
{
    /**
     * The publication reports no filesystem-relative paths.
     */
    case NotApplicable = 'not-applicable';
    /**
     * Accepted file reports exhaust the publication filesystem result.
     */
    case Complete = 'complete';
}
