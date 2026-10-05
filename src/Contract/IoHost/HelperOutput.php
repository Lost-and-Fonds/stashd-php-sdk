<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * One nonempty arbitrary byte chunk from the live stdout or stderr channel; bytes are not lines.
 */
final readonly class HelperOutput
{
    /**
     * Create the helper output.
     *
     * @param HelperOutputStream $channel The original stdout or stderr channel of these bytes.
     * @param list<int> $bytes Nonempty unmodified output bytes, including carriage returns and invalid text encodings.
     */
    public function __construct(
        public HelperOutputStream $channel,
        public array $bytes,
    ) {}
}
