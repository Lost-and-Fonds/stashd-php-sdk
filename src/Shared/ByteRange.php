<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use NoDiscard;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Selects bytes to read from a saved file.
 */
final readonly class ByteRange
{
    /**
     * Select a starting byte and an optional number of bytes to read.
     *
     * @param int $offset Nonnegative byte offset from the start of the file.
     * @param int|null $length Nonnegative byte count; null reads to the end.
     */
    public function __construct(
        public int $offset,
        public ?int $length = null,
    ) {
        if ($offset < 0 || ($length !== null && $length < 0)) {
            throw new ProtocolViolation('Byte range values cannot be negative');
        }
    }

    /**
     * Return the requested count limited to the bytes available, or null when the offset is past EOF.
     */
    #[NoDiscard]
    public function extent(int $authoritativeSize): ?int
    {
        if ($authoritativeSize < 0) {
            throw new ProtocolViolation('Byte size cannot be negative');
        }

        if ($this->offset > $authoritativeSize) {
            return null;
        }

        $remaining = $authoritativeSize - $this->offset;

        return $this->length === null ? $remaining : min($this->length, $remaining);
    }
}
