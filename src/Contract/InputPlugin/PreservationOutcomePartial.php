<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\InputHost\Deficiency;

/**
 * The partial form of preservation outcome.
 */
final readonly class PreservationOutcomePartial implements PreservationOutcome
{
    /**
     * Data carried by this result.
     * @var list<Deficiency>
     */
    public array $value;

    /**
     * Create this result with its associated data.
     * @param list<Deficiency> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
