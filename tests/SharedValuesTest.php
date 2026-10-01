<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Shared\ByteRange;
use Stashd\PluginSdk\Shared\Metadata;
use Stashd\PluginSdk\Shared\Unsigned64;

it('keeps schema identities and metadata text opaque', function (): void {
    $text = '{ "credential": "opaque-looking-value", "n": 1.00 }';
    $facet = new Metadata(' ', $text);
    expect($facet->schema)->toBe(' ')->and($facet->json)->toBe($text);
});

it('rejects structurally invalid metadata without repairing it', function (string $schema, string $json): void {
    expect(fn() => new Metadata($schema, $json))->toThrow(ProtocolViolation::class);
})->with([['', '{}'], ['x', '[]'], ['x', 'null'], ['x', '{"a":{"x":1,"x":2}}'], ['x', '{']]);

it('applies the frozen byte-range vectors without offset-plus-length overflow', function (string $size, string $offset, ?string $length, ?string $expected): void {
    $range = new ByteRange(new Unsigned64($offset), $length === null ? null : new Unsigned64($length));
    expect($range->extent(new Unsigned64($size))?->decimal)->toBe($expected);
})->with([
    ['100', '0', null, '100'], ['100', '25', null, '75'], ['100', '20', '10', '10'],
    ['100', '90', '20', '10'], ['100', '99', '18446744073709551615', '1'],
    ['100', '100', null, '0'], ['100', '100', '0', '0'], ['100', '100', '50', '0'],
    ['100', '101', null, null], ['100', '101', '0', null], ['100', '50', '0', '0'],
    ['0', '0', null, '0'], ['0', '0', '18446744073709551615', '0'], ['0', '1', '0', null],
    ['100', '50', '18446744073709551615', '50'],
    ['18446744073709551615', '1', null, '18446744073709551614'],
]);
