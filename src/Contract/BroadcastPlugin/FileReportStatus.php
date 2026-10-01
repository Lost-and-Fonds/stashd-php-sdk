<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastPlugin;

/**
 * Closed canonical cases for broadcast-plugin.file-report-status; spelling is protocol identity.
 */
enum FileReportStatus: string
{
    case NotApplicable = 'not-applicable';
    case Complete = 'complete';
}
