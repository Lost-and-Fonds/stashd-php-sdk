<?php

declare(strict_types=1);

use Stashd\PluginSdk\Contract\InputHost\DiscoveryFinishExhaustive;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryProgressFinished;
use Stashd\PluginSdk\Runtime\Codec\AuthorValues;
use Stashd\PluginSdk\Runtime\Codec\Generated\InputHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\InputPluginCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('rejects contract byte counts that PHP cannot represent in author values', function (): void {
    $value = Json::decode('{"reference":"opaque-stage-1","media-type":null,"size-bytes":"18446744073709551615","metadata":[]}');
    expect(fn() => IoHostCodec::decodeStagedArtifact($value))->toThrow(ProtocolViolation::class);
});

it('rejects author-facing byte counts beyond PHP integer capacity', function (): void {
    expect(AuthorValues::authorSize((string) PHP_INT_MAX))->toBe(PHP_INT_MAX)
        ->and(fn() => AuthorValues::authorSize('18446744073709551615'))->toThrow(ProtocolViolation::class);
});

it('distinguishes payload-bearing null from payloadless variant cases', function (): void {
    $value = Json::decode('{"tag":"finished","value":{"tag":"exhaustive","value":null}}');
    $progress = InputHostCodec::decodeDiscoveryProgress($value);
    expect($progress)->toBeInstanceOf(DiscoveryProgressFinished::class)
        ->and($progress->value)->toBeInstanceOf(DiscoveryFinishExhaustive::class)
        ->and($progress->value->value)->toBeNull();
    expect(fn() => InputHostCodec::decodeDiscoveryFinish('exhaustive'))->toThrow(ProtocolViolation::class);
});

it('rejects legacy universal media fields in exact Input records', function (): void {
    $value = Json::decode('{"id":"opaque","canonical-reference":null,"estimated-item-count":null,"size-bytes":null,"size-estimated":false,"metadata":[],"duration":10}');
    expect(fn() => InputPluginCodec::decodeResolvedInput($value))->toThrow(ProtocolViolation::class);
});

it('rejects unknown variant tags and extra tagged members', function (): void {
    expect(fn() => InputHostCodec::decodeDiscoveryProgress(Json::decode('{"tag":"old","value":null}')))->toThrow(ProtocolViolation::class)
        ->and(fn() => InputHostCodec::decodeDiscoveryFinish(Json::decode('{"tag":"exhaustive","value":null,"extra":true}')))->toThrow(ProtocolViolation::class);
});
