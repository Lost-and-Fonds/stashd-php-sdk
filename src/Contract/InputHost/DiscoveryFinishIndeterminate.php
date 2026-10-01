<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Canonical indeterminate branch of input-host.discovery-finish.
 */
final readonly class DiscoveryFinishIndeterminate implements DiscoveryFinish
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var OutcomeDiagnostic
     */
    public OutcomeDiagnostic $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param OutcomeDiagnostic $value
     */
    public function __construct(OutcomeDiagnostic $value)
    {
        $this->value = $value;
    }
}
