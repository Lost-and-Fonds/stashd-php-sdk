<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\Codec\Envelope;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('validates hello without conflating contract identity with protocol version', function (): void {
    $frame = Json::decode('{"protocol":1,"id":"hello","kind":"response","result":{"protocol":1,"min":1,"max":1,"max-frame-bytes":4294967295}}');
    expect(Envelope::hello($frame, 'hello'))->toBe(4294967295);
});

it('rejects invalid response envelopes without skipping them', function (string $json): void {
    expect(fn() => Envelope::response(Json::decode($json), 'call', 'invocation'))->toThrow(ProtocolViolation::class);
})->with([
    '{"protocol":1,"id":"call","kind":"response","invocation":"invocation","error":"failed"}',
    '{"protocol":1,"id":"call","kind":"response","invocation":"invocation","result":null,"error":null}',
    '{"protocol":1,"id":"other","kind":"response","invocation":"invocation","result":null}',
    '{"protocol":1,"id":"call","kind":"response","invocation":"other","result":null}',
    '{"protocol":1,"id":"","kind":"response","invocation":"invocation","result":null}',
    '{"protocol":1.0,"id":"call","kind":"response","invocation":"invocation","result":null}',
]);

it('retains typed WIT errors inside canonical result', function (): void {
    $frame = Json::decode('{"protocol":1,"id":"call","kind":"response","invocation":"invocation","result":{"error":"denied"}}');
    Envelope::response($frame, 'call', 'invocation');
    expect($frame->result->error)->toBe('denied');
});

it('requires exact lifecycle parameters and qualified method names', function (): void {
    $frame = Json::decode('{"protocol":1,"id":"call","kind":"request","invocation":"invocation","method":"acquire","params":{}}');
    expect(fn() => Envelope::request($frame))->toThrow(ProtocolViolation::class);
    $frame->method = 'stashd:plugin/input-plugin.acquire';
    Envelope::request($frame);
    $frame->params = [];
    expect(fn() => Envelope::request($frame))->toThrow(ProtocolViolation::class);
});
