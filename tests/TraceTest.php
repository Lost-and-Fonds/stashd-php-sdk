<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;

it('emits no disabled-mode bytes', function (): void {
    $sink = fopen('php://memory', 'w+b');
    $trace = new Trace(TraceLevel::Off, $sink);
    $trace->emit(TraceLevel::Basic, 'startup');
    expect(ftell($sink))->toBe(0)->and($trace->enabled(TraceLevel::Ludicrous))->toBeFalse();
    fclose($sink);
});

it('redacts secret-bearing fields even at maximum verbosity', function (): void {
    $sink = fopen('php://memory', 'w+b');
    $trace = new Trace(TraceLevel::Ludicrous, $sink);
    $trace->emit(TraceLevel::Wire, 'frame.received', ['id' => 'call-1', 'invocation' => 'inv-1', 'bytes' => 123,
        'Authorization' => 'Bearer secret', 'Cookie' => 'secret', 'raw-secret' => 'secret',
        'helper-environment' => 'secret', 'body' => 'secret']);
    rewind($sink);
    $output = stream_get_contents($sink);
    $record = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
    expect($output)->not->toContain('Bearer secret')
        ->and($record['context']['Authorization'])->toBe('[redacted]')
        ->and($record['context']['body'])->toBe('[redacted]')
        ->and($record['context']['id'])->toBe('call-1')
        ->and($record['context']['bytes'])->toBe(123)
        ->and($record['elapsed-ns'])->toBeGreaterThanOrEqual(0);
    fclose($sink);
});

it('rejects diagnostics routed to RPC stdout', function (): void {
    expect(fn() => new Trace(TraceLevel::Ludicrous, STDOUT))->toThrow(InvalidArgumentException::class);
});

it('honors level filtering and the documented maximum environment switch', function (): void {
    $previous = getenv('STASHD_PHP_SDK_TRACE');
    putenv('STASHD_PHP_SDK_TRACE=ludicrous');

    try {
        expect(TraceLevel::fromEnvironment())->toBe(TraceLevel::Ludicrous);
    } finally {
        putenv($previous === false ? 'STASHD_PHP_SDK_TRACE' : 'STASHD_PHP_SDK_TRACE=' . $previous);
    }

    $sink = fopen('php://memory', 'w+b');
    $trace = new Trace(TraceLevel::Basic, $sink);
    $trace->emit(TraceLevel::Wire, 'frame.received');
    expect(ftell($sink))->toBe(0);
    $trace->emit(TraceLevel::Basic, 'startup');
    expect(ftell($sink))->toBeGreaterThan(0);
    fclose($sink);
});
