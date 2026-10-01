<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\InputHost\Deficiency;

/**
 * Canonical partial branch of input-plugin.preservation-outcome.
 */
final readonly class PreservationOutcomePartial implements PreservationOutcome
{
    /**
     * Payload belonging only to this variant case, preserving optional absence and list order.
     * @var list<Deficiency>
     */
    public array $value;

    /**
     * Construct this specific branch without string tags or raw wire objects.
     * @param list<Deficiency> $value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }
}
