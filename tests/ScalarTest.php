<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\Codec\Scalar;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('accepts exact integer boundary strings', function (string $type, string $value): void {
    Scalar::validate($type, $value);
    expect(true)->toBeTrue();
})->with([
    ['u64', '0'], ['u64', '18446744073709551615'],
    ['s64', '-9223372036854775808'], ['s64', '9223372036854775807'],
]);

it('rejects malformed and overflowing primitives', function (string $type, mixed $value): void {
    expect(fn() => Scalar::validate($type, $value))->toThrow(ProtocolViolation::class);
})->with([
    ['u64', '18446744073709551616'], ['s64', '9223372036854775808'],
    ['s64', '-9223372036854775809'], ['s64', '-0'], ['s64', '+1'],
    ['s64', '01'], ['s64', '1.0'], ['s64', '1e2'], ['s64', "1\n"],
    ['u64', '-1'], ['u64', 1], ['u8', 256], ['u8', -1], ['u8', 1.0],
    ['u32', 4294967296], ['s32', -2147483649], ['f64', INF], ['f64', NAN],
    ['f32', 3.5e38], ['bool', 1], ['string', "\xff"],
]);
