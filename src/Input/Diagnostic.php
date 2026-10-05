<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

use Stashd\PluginSdk\Shared\Metadata;

/**
 * An explanation of incomplete or uncertain discovery coverage.
 */
final readonly class Diagnostic
{
    /**
     * Describe incomplete coverage and supporting evidence.
     * @param string $message The reason discovery could not confirm full coverage.
     * @param list<Metadata> $evidence Plugin-owned evidence for this diagnosis.
     */
    public function __construct(public string $message, public array $evidence = []) {}
}
