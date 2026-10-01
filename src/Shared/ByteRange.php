<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use NoDiscard;

/**
 * Forward byte interval; authority must be validated by the host before size-based range evaluation.
 */
final readonly class ByteRange
{
    /**
     * Zero-based starting offset; an offset exactly at EOF is valid.
     */
    public Unsigned64 $offset;

    /**
     * Requested extent, or null for all remaining bytes; zero requests an empty stream.
     */
    public ?Unsigned64 $length;

    /**
     * Preserve requested magnitudes independently of negotiated RPC frame sizes.
     */
    public function __construct(Unsigned64 $offset, ?Unsigned64 $length = null)
    {
        $this->offset = $offset;
        $this->length = $length;
    }

    /**
     * Return the clamped extent, or null for a denied offset, after authority validation.
     */
    #[NoDiscard]
    public function extent(Unsigned64 $authoritativeSize): ?Unsigned64
    {
        if ($this->offset->compare($authoritativeSize) > 0) {
            return null;
        }

        $remaining = $authoritativeSize->subtract($this->offset);

        return $this->length !== null && $this->length->compare($remaining) < 0 ? $this->length : $remaining;
    }
}
