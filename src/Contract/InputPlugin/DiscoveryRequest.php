<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\InputHost\DiscoveryContinuation;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryRefreshState;

/**
 * Settings for a discovery run; these stay fixed when the run resumes.
 */
final readonly class DiscoveryRequest
{
    /**
     * Create the discovery request.
     *
     * @param string $inputId Input identifier returned when the source was resolved.
     * @param DiscoveryIntent $intent Whether to refresh known work or enumerate the full input.
     * @param list<InputOption> $options Settings selected for this work.
     * @param DiscoveryContinuation|null $continuation Restart point for unfinished work; null starts a new run.
     * @param DiscoveryRefreshState|null $refreshState Baseline from a completed run; null when none is available.
     * @param int $maximumItemsPerBatch Positive upper limit on items per batch; frame size may require fewer.
     */
    public function __construct(
        public string $inputId,
        public DiscoveryIntent $intent,
        public array $options,
        public ?DiscoveryContinuation $continuation,
        public ?DiscoveryRefreshState $refreshState,
        public int $maximumItemsPerBatch,
    ) {}
}
