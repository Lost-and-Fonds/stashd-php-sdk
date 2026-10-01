<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use RuntimeException;

/**
 * Ordinary typed host-capability failure, distinct from lifecycle outcomes and channel violations.
 */
final class HostFailure extends RuntimeException
{
    /**
     * Canonical typed host error branch, retained without logging its potentially sensitive payload.
     */
    public readonly object $detail;

    /**
     * Preserve the decoded error for deliberate plugin-specific recovery decisions.
     */
    public function __construct(object $detail)
    {
        parent::__construct('Host capability returned a typed failure');
        $this->detail = $detail;
    }
}
