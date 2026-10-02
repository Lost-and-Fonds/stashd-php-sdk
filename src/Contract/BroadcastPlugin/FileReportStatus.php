<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Closed canonical cases for broadcast-plugin.file-report-status; spelling is protocol identity.
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
