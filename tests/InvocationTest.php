<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

it('correlates imported calls independently of an outstanding lifecycle', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $sink = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write((object) ['protocol' => 1, 'id' => 'plugin-2', 'kind' => 'response', 'invocation' => 'inv', 'result' => null]);
    rewind($input);
    $invocation = new Invocation('inv', 'plugin-1', new FrameChannel($input, $output), new Trace(TraceLevel::Ludicrous, $sink), ['stashd:plugin/logging-host']);
    expect($invocation->call('stashd:plugin/logging-host.log', (object) ['message' => 'diagnostic']))->toBeNull();
    rewind($output);
    expect($host->read()->id)->toBe('plugin-2');
    $invocation->cleanup();
    expect(fn() => $invocation->call('stashd:plugin/logging-host.log', (object) ['message' => 'late']))->toThrow(ProtocolViolation::class);
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('invalidates resources and channel on unmatched imported responses', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $sink = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write((object) ['protocol' => 1, 'id' => 'unrelated', 'kind' => 'response', 'invocation' => 'inv', 'result' => null]);
    rewind($input);
    $channel = new FrameChannel($input, $output);
    $invocation = new Invocation('inv', 'host-1', $channel, new Trace(TraceLevel::Off, $sink), ['stashd:plugin/logging-host']);
    $invocation->resources->accept('inv', 'resource', 'type');
    expect(fn() => $invocation->call('stashd:plugin/logging-host.log', (object) ['message' => 'test']))->toThrow(ProtocolViolation::class)
        ->and(fn() => $invocation->resources->requireOwned('inv', 'resource', 'type'))->toThrow(ProtocolViolation::class)
        ->and(fn() => $channel->read())->toThrow(ProtocolViolation::class);
    fclose($input);
    fclose($output);
    fclose($sink);
});
