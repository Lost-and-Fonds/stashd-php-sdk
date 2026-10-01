<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * Immutable input-host.deficiency contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class Deficiency
{
    /**
     * Canonical disposition value; retained in contract order without normalization.
     * @var DeficiencyDisposition
     */
    public DeficiencyDisposition $disposition;

    /**
     * Canonical diagnostic value; retained in contract order without normalization.
     * @var OutcomeDiagnostic
     */
    public OutcomeDiagnostic $diagnostic;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param DeficiencyDisposition $disposition
     * @param OutcomeDiagnostic $diagnostic
     */
    public function __construct(
        DeficiencyDisposition $disposition,
        OutcomeDiagnostic $diagnostic,
    ) {
        $this->disposition = $disposition;
        $this->diagnostic = $diagnostic;
    }
}
