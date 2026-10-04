<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Examples\ProgressBroadcast;
use Stashd\PluginSdk\Helper\Cancelled;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helper\Exited;
use Stashd\PluginSdk\Helper\Failed;
use Stashd\PluginSdk\Helper\Output;
use Stashd\PluginSdk\Helper\OutputStream;
use Stashd\PluginSdk\Helper\TimedOut;
use Stashd\PluginSdk\Helper\Writer;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\ExportRunner;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;

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

it('keeps a staged writer reusable when helper arguments fail before transfer', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([]);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
    expect(fn() => (new Helpers($invocation))->start('approved', credentials: [new Credential('key', 'reference')], output: $writer))
        ->toThrow(InvalidArgumentException::class);
    expect($writer->transfer())->toBeInstanceOf(RemoteStagedWriter::class);
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('prevents reuse of a writer after it is handed to a helper', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        null,
    ]);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
    $process = (new Helpers($invocation))->start('approved', output: $writer);

    foreach (['transfer', 'finish', 'close'] as $method) {
        expect(fn() => $writer->{$method}())->toThrow(LogicException::class);
    }

    expect(fn() => $writer->write('again'))->toThrow(LogicException::class);
    $process->close();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('streams remaining events after cancellation and keeps terminal followed by sticky EOF', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        (object) ['tag' => 'output', 'value' => (object) ['channel' => 'stderr', 'bytes' => [0, 13, 255]]],
        null,
        (object) ['tag' => 'terminal', 'value' => 'cancelled'],
        null,
        null,
    ]);
    $process = (new Helpers($invocation))->start('approved');
    $events = $process->events();
    expect($events->current())->toBeInstanceOf(Output::class)
        ->and($events->current()->stream)->toBe(OutputStream::Stderr)
        ->and($events->current()->bytes)->toBe("\x00\r\xff");
    $process->cancel();
    $events->next();
    expect($events->current())->toBeInstanceOf(Cancelled::class);
    $events->next();
    expect($events->valid())->toBeFalse()->and(iterator_to_array($process->events()))->toBe([]);
    $process->close();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('exposes timeout and host failure after accepted output without returning staged writers', function (): void {
    foreach (['timed-out', 'failed'] as $outcome) {
        $terminal = $outcome === 'failed' ? (object) ['tag' => 'failed', 'value' => 'host lost helper'] : 'timed-out';
        [$invocation, $host, $input, $output, $sink] = helperFixture([
            (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
            (object) ['tag' => 'output', 'value' => (object) ['channel' => 'stderr', 'bytes' => [65]]],
            (object) ['tag' => 'terminal', 'value' => $terminal],
            null,
            null,
        ]);
        $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
        $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
        $process = (new Helpers($invocation))->start('approved', output: $writer);
        $events = iterator_to_array($process->events());
        expect($events)->toHaveCount(2)->and($events[0])->toBeInstanceOf(Output::class)
            ->and($events[0]->bytes)->toBe('A')
            ->and($events[1])->toBeInstanceOf($outcome === 'failed' ? Failed::class : TimedOut::class);

        if ($events[1] instanceof Failed) {
            expect($events[1]->detail)->toBe('host lost helper');
        }

        expect(iterator_to_array($process->events()))->toBe([])
            ->and(fn() => $writer->finish())->toThrow(LogicException::class)
            ->and(fn() => $invocation->resources->returnTransferred('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer'))->toThrow(ProtocolViolation::class);
        $process->close();
        $invocation->cleanup();
        fclose($input);
        fclose($output);
        fclose($sink);
    }
});

it('discards staged output on cancellation while delivering remaining stderr and terminal', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        null,
        (object) ['tag' => 'output', 'value' => (object) ['channel' => 'stderr', 'bytes' => [66]]],
        (object) ['tag' => 'terminal', 'value' => 'cancelled'],
        null,
        null,
    ]);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
    $process = (new Helpers($invocation))->start('approved', output: $writer);
    $process->cancel();
    $events = iterator_to_array($process->events());
    expect($events)->toHaveCount(2)->and($events[0])->toBeInstanceOf(Output::class)
        ->and($events[0]->bytes)->toBe('B')
        ->and($events[1])->toBeInstanceOf(Cancelled::class)
        ->and(fn() => $writer->finish())->toThrow(LogicException::class)
        ->and(fn() => $invocation->resources->returnTransferred('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer'))->toThrow(ProtocolViolation::class);
    expect(iterator_to_array($process->events()))->toBe([]);
    $process->close();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('returns a staged writer on normal exit even when the code is nonzero', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        (object) ['tag' => 'terminal', 'value' => (object) ['tag' => 'exited', 'value' => (object) [
            'code' => 2, 'output' => ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', 'writer-1'),
        ]]],
        null,
        null,
        null,
    ]);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
    $process = (new Helpers($invocation))->start('approved', output: $writer);
    $events = iterator_to_array($process->events());
    expect($events)->toHaveCount(1)->and($events[0])->toBeInstanceOf(Exited::class)
        ->and($events[0]->code)->toBe(2)
        ->and($events[0]->output)->toBeInstanceOf(Writer::class);
    expect(fn() => $writer->finish())->toThrow(LogicException::class);
    $events[0]->output->close();
    expect(fn() => $events[0]->output->write('stale'))->toThrow(ProtocolViolation::class);
    $process->close();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('closes a running public process without polling events or reviving its staged writer', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        null,
    ]);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
    $process = (new Helpers($invocation))->start('approved', output: $writer);
    $process->close();
    expect(fn() => iterator_to_array($process->events()))->toThrow(ProtocolViolation::class)
        ->and(fn() => $process->cancel())->toThrow(ProtocolViolation::class)
        ->and(fn() => $writer->finish())->toThrow(LogicException::class)
        ->and(fn() => $invocation->resources->returnTransferred('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer'))->toThrow(ProtocolViolation::class);
    rewind($output);
    expect($host->read()->method)->toBe('stashd:plugin/io-host.start-helper')
        ->and($host->read()->method)->toBe('stashd:plugin/rpc.resource-drop')
        ->and($host->read())->toBeNull();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('keeps the first terminal outcome when cancellation arrives after exit', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        (object) ['tag' => 'terminal', 'value' => (object) ['tag' => 'exited', 'value' => (object) ['code' => 0, 'output' => null]]],
        null,
        null,
        null,
    ]);
    $process = (new Helpers($invocation))->start('approved');
    $events = $process->events();
    expect($events->current())->toBeInstanceOf(Exited::class);
    $process->cancel();
    $events->next();
    expect($events->valid())->toBeFalse()->and(iterator_to_array($process->events()))->toBe([]);
    $process->close();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('invalidates retained public process and returned writer at invocation cleanup', function (): void {
    [$invocation, $host, $input, $output, $sink] = helperFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        (object) ['tag' => 'terminal', 'value' => (object) ['tag' => 'exited', 'value' => (object) [
            'code' => 0, 'output' => ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', 'writer-1'),
        ]]],
        null,
    ]);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer-1'));
    $process = (new Helpers($invocation))->start('approved', output: $writer);
    $events = iterator_to_array($process->events());
    expect($events[0])->toBeInstanceOf(Exited::class)->and($events[0]->output)->toBeInstanceOf(Writer::class);
    $invocation->cleanup();
    expect(fn() => iterator_to_array($process->events()))->toThrow(ProtocolViolation::class)
        ->and(fn() => $process->cancel())->toThrow(ProtocolViolation::class)
        ->and(fn() => $events[0]->output->finish())->toThrow(ProtocolViolation::class)
        ->and(fn() => $writer->finish())->toThrow(LogicException::class)
        ->and(fn() => $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer'))->toThrow(ProtocolViolation::class);
    fclose($input);
    fclose($output);
    fclose($sink);
});
