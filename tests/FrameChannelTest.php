<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('counts encoded bytes and enforces directional maxima independently', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $channel = new FrameChannel($input, $output);
    $channel->negotiate(4096, 8192);
    $frame = (object) ['text' => str_repeat('é', 3000)];
    $channel->write($frame);
    rewind($output);
    expect(fread($output, 4))->toBe(pack('N', strlen(Json::encode($frame))));
    expect(stream_get_contents($output))->toBe(Json::encode($frame));
    fclose($input);
    fclose($output);
});

it('accepts the exact bootstrap boundary and rejects one byte above before decoding', function (): void {
    foreach ([4096, 4097] as $size) {
        $input = fopen('php://memory', 'w+b');
        $output = fopen('php://memory', 'w+b');
        $payload = '{"v":"' . str_repeat('x', $size - 8) . '"}';
        fwrite($input, pack('N', $size) . $payload);
        rewind($input);
        $channel = new FrameChannel($input, $output);

        if ($size === 4096) {
            expect($channel->read()->v)->toHaveLength(4088);
        } else {
            expect(fn() => $channel->read())->toThrow(ProtocolViolation::class);
            expect(ftell($input))->toBe(4);
            expect(fn() => $channel->read())->toThrow(ProtocolViolation::class, 'unusable');
        }

        fclose($input);
        fclose($output);
    }
});

it('never transmits an oversized probe', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $channel = new FrameChannel($input, $output);
    expect(fn() => $channel->write((object) ['data' => str_repeat('x', 4096)]))->toThrow(ProtocolViolation::class);
    expect(ftell($output))->toBe(0);
    fclose($input);
    fclose($output);
});

it('rejects malformed framing and invalid roots', function (string $bytes): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    fwrite($input, $bytes);
    rewind($input);
    expect(fn() => (new FrameChannel($input, $output))->read())->toThrow(ProtocolViolation::class);
    fclose($input);
    fclose($output);
})->with([
    "\0", pack('N', 0), pack('N', 8) . '{}', pack('N', 2) . '[]',
    pack('N', 13) . '{"x":1,"x":2}', pack('N', 1) . "\xff",
]);

it('validates advertised range without a smaller private ceiling', function (int $maximum, bool $valid): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $channel = new FrameChannel($input, $output);

    if ($valid) {
        $channel->negotiate($maximum, $maximum);
        expect($channel->read())->toBeNull();
    } else {
        expect(fn() => $channel->negotiate($maximum, 4096))->toThrow(ProtocolViolation::class);
    }

    fclose($input);
    fclose($output);
})->with([[0, false], [4095, false], [4096, true], [4294967295, true], [4294967296, false]]);
