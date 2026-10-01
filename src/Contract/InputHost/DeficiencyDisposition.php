<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Closed canonical cases for input-host.deficiency-disposition; spelling is protocol identity.
 */
enum DeficiencyDisposition: string
{
    case Retryable = 'retryable';
    case Terminal = 'terminal';
    case Unknown = 'unknown';
}
