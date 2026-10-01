<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Exact WIT primitive validation without floating-point integer coercion.
 */
final class Scalar
{
    /**
     * Check a primitive at the wire boundary and preserve its canonical representation.
     */
    public static function validate(string $type, mixed $value): void
    {
        $valid = match ($type) {
            'bool' => is_bool($value),
            'string' => is_string($value) && preg_match('//u', $value) === 1,
            'u8' => self::integer($value, 0, 255),
            'u16' => self::integer($value, 0, 65535),
            'u32' => self::integer($value, 0, 4294967295),
            's8' => self::integer($value, -128, 127),
            's16' => self::integer($value, -32768, 32767),
            's32' => self::integer($value, -2147483648, 2147483647),
            'u64' => self::decimal($value, false),
            's64' => self::decimal($value, true),
            'f32' => (is_int($value) || is_float($value)) && is_finite((float) $value) && abs($value) <= 3.4028234663852886e38,
            'f64' => (is_int($value) || is_float($value)) && is_finite((float) $value),
            default => false,
        };

        if (!$valid) {
            throw new ProtocolViolation('Invalid WIT primitive: ' . $type);
        }
    }

    /**
     * Check small integer bounds without accepting fractional or exponential JSON numbers.
     */
    private static function integer(mixed $value, int $minimum, int $maximum): bool
    {
        return is_int($value) && $value >= $minimum && $value <= $maximum;
    }

    /**
     * Compare canonical decimal magnitudes lexically, retaining all 64 bits.
     */
    private static function decimal(mixed $value, bool $signed): bool
    {
        if (!is_string($value) || preg_match('/\A(?:0|-?[1-9][0-9]*)\z/D', $value) !== 1) {
            return false;
        }

        $negative = str_starts_with($value, '-');

        if ($negative && !$signed) {
            return false;
        }

        $magnitude = $negative ? substr($value, 1) : $value;
        $maximum = $signed ? ($negative ? '9223372036854775808' : '9223372036854775807') : '18446744073709551615';

        return strlen($magnitude) < strlen($maximum)
            || (strlen($magnitude) === strlen($maximum) && strcmp($magnitude, $maximum) <= 0);
    }
}
