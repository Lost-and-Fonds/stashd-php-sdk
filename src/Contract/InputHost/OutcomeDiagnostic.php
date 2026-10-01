<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * Immutable input-host.outcome-diagnostic contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class OutcomeDiagnostic
{
    /**
     * Canonical message value; retained in contract order without normalization.
     * @var string
     */
    public string $message;

    /**
     * Canonical evidence value; retained in contract order without normalization.
     * @var list<PluginMetadata>
     */
    public array $evidence;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $message
     * @param list<PluginMetadata> $evidence
     */
    public function __construct(
        string $message,
        array $evidence,
    ) {
        $this->message = $message;
        $this->evidence = $evidence;
    }
}
