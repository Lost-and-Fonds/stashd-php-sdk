<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Shared\Unsigned64;
use stdClass;

/**
 * Primitive building blocks for generated focused codecs; never exposed to plugin authors.
 */
final class Values
{
    /**
     * Require an exact WIT record before accessing any of its declared members.
     * @param list<string> $fields
     */
    public static function record(mixed $value, array $fields): stdClass
    {
        if (!$value instanceof stdClass) {
            throw new ProtocolViolation('Expected WIT record');
        }

        Envelope::exact($value, $fields);

        return $value;
    }

    /**
     * Require an ordered list rather than silently accepting an object or sparse PHP array.
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new ProtocolViolation('Expected ordered WIT list');
        }

        return $value;
    }

    /**
     * Preserve a Unicode string with no normalization or string coercion.
     */
    public static function text(mixed $value): string
    {
        if (!is_string($value) || preg_match('//u', $value) !== 1) {
            throw new ProtocolViolation('Expected Unicode scalar string');
        }

        return $value;
    }

    /**
     * Decode bounded JSON integer values without accepting fractional representations.
     */
    public static function integer(string $type, mixed $value): int
    {
        Scalar::validate($type, $value);

        if (!is_int($value)) {
            throw new ProtocolViolation('Expected bounded integer');
        }

        return $value;
    }

    /**
     * Decode exact signed decimal strings on the required 64-bit PHP runtime.
     */
    public static function signed(mixed $value): int
    {
        Scalar::validate('s64', $value);

        if (!is_string($value) || PHP_INT_SIZE !== 8) {
            throw new ProtocolViolation('Signed64 requires canonical decimal text and 64-bit PHP');
        }

        return (int) $value;
    }

    /**
     * Decode the full unsigned range without loss of precision.
     */
    public static function unsigned(mixed $value): Unsigned64
    {
        return new Unsigned64(self::text($value));
    }

    /**
     * Require a JSON boolean, never PHP truthiness.
     */
    public static function boolean(mixed $value): bool
    {
        if (!is_bool($value)) {
            throw new ProtocolViolation('Expected boolean');
        }

        return $value;
    }

    /**
     * Accept finite JSON numbers in the declared floating-point range.
     */
    public static function floating(string $type, mixed $value): float
    {
        Scalar::validate($type, $value);

        if (!is_float($value) && !is_int($value)) {
            throw new ProtocolViolation('Expected finite number');
        }

        return (float) $value;
    }
}
