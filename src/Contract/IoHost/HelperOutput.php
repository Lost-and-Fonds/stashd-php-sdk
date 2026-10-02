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
     * The original stdout or stderr channel of these bytes.
     * @var HelperOutputStream
     */
    public HelperOutputStream $channel;

    /**
     * Nonempty unmodified output bytes, including carriage returns and invalid text encodings.
     * @var list<int>
     */
    public array $bytes;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param HelperOutputStream $channel
     * @param list<int> $bytes
     */
    public function __construct(
        HelperOutputStream $channel,
        array $bytes,
    ) {
        $this->channel = $channel;
        $this->bytes = $bytes;
    }
}
