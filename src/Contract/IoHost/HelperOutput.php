<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * One nonempty arbitrary byte chunk from the live stdout or stderr channel; bytes are not lines.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class HelperOutput
{
    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param HelperOutputStream $channel
     * @param list<int> $bytes
     */
    public function __construct(
        public HelperOutputStream $channel,
        public array $bytes,
    ) {}
}
