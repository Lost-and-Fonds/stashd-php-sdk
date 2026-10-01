<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use InvalidArgumentException;
use NoDiscard;
use Stashd\PluginSdk\Runtime\Codec\Scalar;

/**
 * Exact nonnegative 64-bit quantity, including values beyond PHP's signed integer maximum.
 */
final readonly class Unsigned64
{
    /**
     * Canonical decimal magnitude, never rounded through a floating-point value.
     */
    public string $decimal;

    /**
     * Accept a canonical decimal quantity without trimming or numeric coercion.
     */
    public function __construct(string $decimal)
    {
        Scalar::validate('u64', $decimal);
        $this->decimal = $decimal;
    }

    /**
     * Compare magnitudes without overflowing PHP native integers.
     */
    public function compare(self $other): int
    {
        return (strlen($this->decimal) <=> strlen($other->decimal)) ?: strcmp($this->decimal, $other->decimal);
    }

    /**
     * Subtract a lesser or equal magnitude using decimal arithmetic with no intermediate overflow.
     */
    #[NoDiscard]
    public function subtract(self $other): self
    {
        if ($this->compare($other) < 0) {
            throw new InvalidArgumentException('Unsigned subtraction would be negative');
        }

        $right = str_pad($other->decimal, strlen($this->decimal), '0', STR_PAD_LEFT);
        $borrow = 0;
        $result = '';

        for ($index = strlen($this->decimal) - 1; $index >= 0; --$index) {
            $digit = (int) $this->decimal[$index] - (int) $right[$index] - $borrow;
            $borrow = $digit < 0 ? 1 : 0;
            $result = (string) ($digit + 10 * $borrow) . $result;
        }

        return new self(ltrim($result, '0') ?: '0');
    }
}
