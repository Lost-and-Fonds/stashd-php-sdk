<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;

/**
 * An explanation of a result, with supporting metadata.
 */
final readonly class OutcomeDiagnostic
{
    /**
     * Create the outcome diagnostic.
     *
     * @param string $message Human-readable explanation of what happened.
     * @param list<PluginMetadata> $evidence Plugin-owned metadata supporting the explanation.
     */
    public function __construct(
        public string $message,
        public array $evidence,
    ) {}
}
