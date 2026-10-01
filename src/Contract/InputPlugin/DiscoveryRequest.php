<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\InputHost\DiscoveryContinuation;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryRefreshState;

/**
 * Immutable input-plugin.discovery-request contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class DiscoveryRequest
{
    /**
     * Canonical input-id value; retained in contract order without normalization.
     * @var string
     */
    public string $inputId;

    /**
     * Canonical intent value; retained in contract order without normalization.
     * @var DiscoveryIntent
     */
    public DiscoveryIntent $intent;

    /**
     * Canonical options value; retained in contract order without normalization.
     * @var list<InputOption>
     */
    public array $options;

    /**
     * Canonical continuation value; retained in contract order without normalization.
     * @var DiscoveryContinuation|null
     */
    public ?\Stashd\PluginSdk\Contract\InputHost\DiscoveryContinuation $continuation;

    /**
     * Canonical refresh-state value; retained in contract order without normalization.
     * @var DiscoveryRefreshState|null
     */
    public ?\Stashd\PluginSdk\Contract\InputHost\DiscoveryRefreshState $refreshState;

    /**
     * Canonical maximum-items-per-batch value; retained in contract order without normalization.
     * @var int
     */
    public int $maximumItemsPerBatch;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $inputId
     * @param DiscoveryIntent $intent
     * @param list<InputOption> $options
     * @param DiscoveryContinuation|null $continuation
     * @param DiscoveryRefreshState|null $refreshState
     * @param int $maximumItemsPerBatch
     */
    public function __construct(
        string $inputId,
        DiscoveryIntent $intent,
        array $options,
        ?DiscoveryContinuation $continuation,
        ?DiscoveryRefreshState $refreshState,
        int $maximumItemsPerBatch,
    ) {
        $this->inputId = $inputId;
        $this->intent = $intent;
        $this->options = $options;
        $this->continuation = $continuation;
        $this->refreshState = $refreshState;
        $this->maximumItemsPerBatch = $maximumItemsPerBatch;
    }
}
