<?php

declare(strict_types=1);

use Stashd\PluginSdk\Contract\InputPlugin\InputPluginMethod;
use Stashd\PluginSdk\Input\Option;
use Stashd\PluginSdk\Input\Source;
use Stashd\PluginSdk\Runtime\Codec\InputAuthorCodec;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('round trips opaque references and ordered typed source values', function (): void {
    $source = new Source('opaque-reference', [new Option('key', 'first'), new Option('key', 'second'), new Option('enabled', true), new Option('count', PHP_INT_MIN)]);
    $wire = InputAuthorCodec::encodeSource($source);
    expect(InputAuthorCodec::source($wire))->toEqual($source)
        ->and($wire->values[3]->value->value)->toBe((string) PHP_INT_MIN)
        ->and($source->text('key'))->toBe('first');
    expect(InputAuthorCodec::source(InputAuthorCodec::encodeSource(new Source())))->toEqual(new Source())
        ->and(InputAuthorCodec::source(InputAuthorCodec::encodeSource(new Source(values: [new Option('configured', false)]))))->toEqual(new Source(values: [new Option('configured', false)]));
});

it('preserves optional discovered sources when decoding independently acquired items', function (): void {
    $wire = Json::decode('{"id":"item","reference":"acquisition-reference","source":{"reference":"receiving-reference","values":[]},"size-bytes":null,"size-estimated":false,"metadata":[]}');
    expect(InputAuthorCodec::item($wire)->source)->toEqual(new Source('receiving-reference'));
    $wire->source = null;
    expect(InputAuthorCodec::item($wire)->source)->toBeNull();
    unset($wire->source);
    $wire->delegation = (object) ['reference' => 'old-reference'];
    expect(fn() => InputAuthorCodec::item($wire))->toThrow(ProtocolViolation::class);
});

it('rejects obsolete source shapes and delegation methods', function (): void {
    foreach ([[], 'raw-reference', (object) ['reference' => 'raw-reference'], (object) ['reference' => null, 'values' => [], 'provider' => 'target']] as $wire) {
        expect(fn() => InputAuthorCodec::source($wire))->toThrow(ProtocolViolation::class);
    }

    expect(InputPluginMethod::tryFrom('stashd:plugin/input-plugin.resolve-delegation'))->toBeNull()
        ->and(InputPluginMethod::CanResolve->value)->toBe('stashd:plugin/input-plugin.can-resolve')
        ->and(class_exists('Stashd\\PluginSdk\\Input\\ResolveDelegation'))->toBeFalse();
});
