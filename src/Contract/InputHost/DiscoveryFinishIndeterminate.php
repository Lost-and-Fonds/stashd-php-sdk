<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * The indeterminate form of discovery finish.
 */
final readonly class DiscoveryFinishIndeterminate implements DiscoveryFinish
{
    /**
     * Data carried by this result.
     * @var OutcomeDiagnostic
     */
    public OutcomeDiagnostic $value;

    /**
     * Create this result with its associated data.
     * @param OutcomeDiagnostic $value
     */
    public function __construct(OutcomeDiagnostic $value)
    {
        $this->value = $value;
    }
}
