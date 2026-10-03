<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Examples\ProgressBroadcast;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\ExportRunner;

require_once __DIR__ . '/../examples/ProgressBroadcast.php';

it('dispatches a plugin helper through staging, live events, returned writer, reopen and cleanup', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $sink = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write(Json::decode('{"protocol":1,"kind":"response","id":"hello","result":{"protocol":1,"min":1,"max":1,"max-frame-bytes":8192}}'));
    $host->write(Json::decode('{"protocol":1,"kind":"request","id":"host-1","invocation":"inv","method":"stashd:plugin/broadcast-plugin.operation","params":{"request":{"name":"approved","settings":[],"payload":[]},"credentials":[]}}'));
    $host->negotiate(8192, 8192);
    $writer = ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', 'writer-1');
    $responses = [
        ResourceValueCodec::handle('stashd:plugin/io-host.staging-area', 'area-1'),
        (object) ['ok' => $writer],
        null,
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process-1')],
        (object) ['tag' => 'stdout-activity', 'value' => '7'],
        (object) ['tag' => 'output', 'value' => (object) ['channel' => 'stderr', 'bytes' => array_values(unpack('C*', "download 10%\rdownload 20%\r"))]],
        (object) ['tag' => 'terminal', 'value' => (object) ['tag' => 'exited', 'value' => (object) ['code' => 0, 'output' => $writer]]],
        (object) ['ok' => (object) ['reference' => 'opaque', 'media-type' => 'application/octet-stream', 'size-bytes' => '7', 'metadata' => []]],
        null,
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.byte-stream', 'stream-1')],
        (object) ['ok' => array_values(unpack('C*', 'payload'))],
        (object) ['ok' => null],
        null,
        null,
        null,
    ];

    foreach ($responses as $index => $result) {
        $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-' . ($index + 1), 'invocation' => 'inv', 'result' => $result]);
    }

    rewind($input);
    $plugin = new ProgressBroadcast();
    (new ExportRunner(new FrameChannel($input, $output), new Trace(TraceLevel::Ludicrous, $sink)))->run($plugin, 8192);
    expect($plugin->percentages)->toBe([10, 20])->and($plugin->activity)->toBe(['7'])->and($plugin->saved)->toBe('payload');
    rewind($output);
    expect($host->read()->method)->toBe('hello');
    $methods = [];
    $frames = [];

    while (($frame = $host->read()) !== null) {
        $frames[] = $frame;
        $methods[] = $frame->kind === 'request' ? $frame->method : 'lifecycle-response';
    }

    expect($frames[1]->params->{'media-type'})->toBe('application/octet-stream')
        ->and($frames[3]->params->output->{'$resource'}->id)->toBe('writer-1')
        ->and($frames[15]->result->ok->choices)->toBe([]);
    expect($methods)->toBe([
        'stashd:plugin/io-host.open-staging-area',
        'stashd:plugin/io-host.staging-area.create',
        'stashd:plugin/rpc.resource-drop',
        'stashd:plugin/io-host.start-helper',
        'stashd:plugin/io-host.helper-process.next-event',
        'stashd:plugin/io-host.helper-process.next-event',
        'stashd:plugin/io-host.helper-process.next-event',
        'stashd:plugin/io-host.staged-writer.finish',
        'stashd:plugin/rpc.resource-drop',
        'stashd:plugin/io-host.open-staged-artifact',
        'stashd:plugin/io-host.byte-stream.read',
        'stashd:plugin/io-host.byte-stream.read',
        'stashd:plugin/rpc.resource-drop',
        'stashd:plugin/io-host.helper-process.next-event',
        'stashd:plugin/rpc.resource-drop',
        'lifecycle-response',
    ]);
    fclose($input);
    fclose($output);
    fclose($sink);
});
