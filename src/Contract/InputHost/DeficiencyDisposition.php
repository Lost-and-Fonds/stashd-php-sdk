<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Closed canonical cases for input-host.deficiency-disposition; spelling is protocol identity.
 */
enum DeficiencyDisposition: string
{
    /**
     * The known gap may be filled by later preservation work.
     */
    case Retryable = 'retryable';
    /**
     * The known gap cannot be filled by retrying the same work.
     */
    case Terminal = 'terminal';
    /**
     * The producer cannot establish whether the gap can be filled.
     */
    case Unknown = 'unknown';
}
