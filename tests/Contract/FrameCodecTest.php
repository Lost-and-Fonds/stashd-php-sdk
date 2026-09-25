<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\RuntimeFrameCodec;

function framePipe(): array
{
    $pipes = [];
    $process = proc_open(['cat'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    expect(is_resource($process))->toBeTrue();
    stream_set_timeout($pipes[1], 2);

    return [$process, $pipes];
}

function writeRawFrame($stream, string $payload): void
{
    fwrite($stream, pack('N', strlen($payload)) . $payload);
}

it('writes the four-byte big-endian length and reads UTF-8 JSON', function (): void {
    [$process, $pipes] = framePipe();
    RuntimeFrameCodec::write($pipes[0], ['kind' => 'request', 'label' => '雪']);
    fclose($pipes[0]);
    $header = fread($pipes[1], 4);
    $length = unpack('Nlength', $header)['length'];
    $payload = fread($pipes[1], $length);
    expect($header)->toBe(pack('N', strlen($payload)));
    expect(json_decode($payload, true, 512, JSON_THROW_ON_ERROR))->toBe(['kind' => 'request', 'label' => '雪']);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    [$process, $pipes] = framePipe();
    writeRawFrame($pipes[0], json_encode(['kind' => 'response', 'label' => 'é'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    expect(RuntimeFrameCodec::read($pipes[1], 1.0))->toBe(['kind' => 'response', 'label' => 'é']);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
});

it('reads multiple sequential frames from one stream', function (): void {
    [$process, $pipes] = framePipe();
    writeRawFrame($pipes[0], '{"id":"one"}');
    writeRawFrame($pipes[0], '{"id":"two"}');

    expect(RuntimeFrameCodec::read($pipes[1], 1.0))->toBe(['id' => 'one']);
    expect(RuntimeFrameCodec::read($pipes[1], 1.0))->toBe(['id' => 'two']);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
});

it('rejects truncated headers and payloads', function (): void {
    [$process, $pipes] = framePipe();
    fwrite($pipes[0], "\x00\x00");
    fclose($pipes[0]);
    expect(fn() => RuntimeFrameCodec::read($pipes[1], 1.0))->toThrow(RuntimeException::class, 'header is truncated');
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    [$process, $pipes] = framePipe();
    fwrite($pipes[0], pack('N', 8) . '{"id"');
    fclose($pipes[0]);
    expect(fn() => RuntimeFrameCodec::read($pipes[1], 1.0))->toThrow(RuntimeException::class, 'frame is truncated');
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
});

it('rejects out-of-range frame lengths, non-object JSON, and malformed JSON', function (): void {
    [$process, $pipes] = framePipe();
    fwrite($pipes[0], pack('N', 1));
    expect(fn() => RuntimeFrameCodec::read($pipes[1], 1.0))->toThrow(RuntimeException::class, 'size limit');
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    [$process, $pipes] = framePipe();
    writeRawFrame($pipes[0], '[]');
    expect(fn() => RuntimeFrameCodec::read($pipes[1], 1.0))->toThrow(RuntimeException::class, 'not an object');
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    [$process, $pipes] = framePipe();
    writeRawFrame($pipes[0], '{bad json');
    expect(fn() => RuntimeFrameCodec::read($pipes[1], 1.0))->toThrow(JsonException::class);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
});
