<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('rejects every frozen raw duplicate-member vector', function (): void {
    $vectors = json_decode(file_get_contents(__DIR__ . '/../resources/contract/rpc-v1-vectors.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($vectors['raw-duplicate-member-frames'] as $vector) {
        expect(fn() => Json::decode($vector['raw-frame']))->toThrow(ProtocolViolation::class);
    }
});

it('compares decoded keys exactly at every depth', function (string $json): void {
    expect(fn() => Json::decode($json))->toThrow(ProtocolViolation::class);
})->with([
    '{"a":1,"\\u0061":2}',
    '{"items":[{"x":{"same":1,"same":2}}]}',
    '{"0":1,"0":2}',
    '{"":"first","":"second"}',
    '{"bad":"\\ud800"}',
]);

it('preserves objects, lists, opaque serialized documents and key case', function (): void {
    $value = Json::decode('{"a":1,"A":2,"nested":[{},[],"{\\"x\\":1,\\"x\\":2}"]}');
    expect($value)->toBeInstanceOf(stdClass::class)
        ->and($value->nested[0])->toBeInstanceOf(stdClass::class)
        ->and($value->nested[1])->toBe([])
        ->and($value->a)->toBe(1)
        ->and($value->A)->toBe(2);
});

it('rejects nonfinite outbound numeric values', function (): void {
    expect(fn() => Json::encode(INF))->toThrow(ProtocolViolation::class)
        ->and(fn() => Json::encode(NAN))->toThrow(ProtocolViolation::class);
});
