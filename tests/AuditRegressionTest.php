<?php

declare(strict_types=1);

use Stashd\PluginSdk\Broadcast\Action;
use Stashd\PluginSdk\Broadcast\ActionResult;
use Stashd\PluginSdk\Broadcast\Failure;
use Stashd\PluginSdk\Broadcast\Publication;
use Stashd\PluginSdk\Broadcast\Publish;
use Stashd\PluginSdk\Broadcast\PublishedFile;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\Contract\BroadcastPlugin\BroadcastPluginMethod;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Helper\Cancelled;
use Stashd\PluginSdk\Helper\CancelRequested;
use Stashd\PluginSdk\Helper\Stream;
use Stashd\PluginSdk\Helper\Writer;
use Stashd\PluginSdk\Runtime\BroadcastDispatcher;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\RemoteByteStream;
use Stashd\PluginSdk\Runtime\Resource\RemoteHostResource;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;
use Stashd\PluginSdk\Tools;

/**
 * Supply correlated fake host responses for public API regression tests.
 */
function auditFixture(array $results): array
{
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);

    foreach ($results as $index => $result) {
        $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-' . ($index + 1), 'invocation' => 'audit', 'result' => $result]);
    }
    rewind($input);
    $invocation = new Invocation('audit', 'host', new FrameChannel($input, $output), new Trace(TraceLevel::Off, fopen('php://memory', 'w+b')), ['stashd:plugin/io-host', 'stashd:plugin/broadcast-host']);

    return [$invocation, $host, $output];
}

it('revokes public stream authority after a caught malformed response', function (): void {
    [$invocation] = auditFixture([(object) ['ok' => 'invalid']]);
    $invocation->resources->accept('audit', 'stream', 'stashd:plugin/io-host.byte-stream');
    $stream = new Stream(new RemoteByteStream($invocation, 'stream'));
    expect(fn() => iterator_to_array($stream->chunks()))->toThrow(ProtocolViolation::class);
    expect(fn() => $invocation->requireActive())->toThrow(ProtocolViolation::class);
});

it('preserves writers and input on local helper argument failure', function (): void {
    [$invocation, $host, $output] = auditFixture([]);
    $invocation->resources->accept('audit', 'writer', 'stashd:plugin/io-host.staged-writer');
    $invocation->resources->accept('audit', 'stream', 'stashd:plugin/io-host.byte-stream');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer'));
    $stream = new Stream(new RemoteByteStream($invocation, 'stream'));
    expect(fn() => (new Tools($invocation))->start('approved', [123], $stream, $writer))->toThrow(ProtocolViolation::class);
    expect($writer->transfer())->toBeInstanceOf(RemoteStagedWriter::class)
        ->and($stream->transfer())->toBeInstanceOf(RemoteByteStream::class)
        ->and(ftell($output))->toBe(0);
});

it('writes exact public binary bytes across frame boundaries', function (): void {
    [$invocation, $host, $output] = auditFixture(array_fill(0, 20, (object) ['ok' => null]));
    $invocation->resources->accept('audit', 'writer', 'stashd:plugin/io-host.staged-writer');
    $bytes = str_repeat("\x00\xff\r", 3000);
    (new Writer(new RemoteStagedWriter($invocation, 'writer')))->write($bytes);
    rewind($output);
    $actual = '';
    $count = 0;

    while (($frame = $host->read()) !== null) {
        expect(strlen(Json::encode($frame)))->toBeLessThanOrEqual(4096);
        $actual .= pack('C*', ...$frame->params->bytes);
        ++$count;
    }
    expect($actual)->toBe($bytes)->and($count)->toBeGreaterThan(1);
    $invocation->requireActive();
});

it('delivers invocation cancellation through the convenience iterator before output', function (): void {
    $poll = static fn($state) => (object) ['invocation-cancel-requested' => true, 'state' => $state];
    [$invocation, $host, $output] = auditFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        $poll('pending'),
        null,
        $poll((object) ['tag' => 'event', 'value' => (object) ['tag' => 'output', 'value' => (object) ['channel' => 'stderr', 'bytes' => [65]]]]),
        $poll((object) ['tag' => 'event', 'value' => (object) ['tag' => 'terminal', 'value' => 'cancelled']]),
        $poll('end'),
        $poll('end'),
    ]);
    $process = (new Tools($invocation))->start('approved');
    $events = $process->events();
    expect($events->current())->toBeInstanceOf(CancelRequested::class);
    $process->cancel();
    $events->next();
    expect($events->current()->bytes)->toBe('A');
    $events->next();
    expect($events->current())->toBeInstanceOf(Cancelled::class);
    $events->next();
    expect($events->valid())->toBeFalse()->and($process->poll(0)->cancelRequested)->toBeTrue();
    rewind($output);
    $host->read();
    expect($host->read()->params->{'wait-ms'})->toBe(1000);
});

it('encodes native signed boundaries through the recursive resource codec', function (): void {
    [$invocation] = auditFixture([]);

    foreach ([0, PHP_INT_MIN, PHP_INT_MAX] as $value) {
        $transfers = [];
        expect(ResourceValueCodec::encode($value, ['kind' => 'scalar', 'name' => 's64'], 'input-host', $invocation, $transfers))->toBe((string) $value);
    }
});


it('keeps ordinary staging failures typed and the invocation usable', function (): void {
    foreach (['denied', (object) ['tag' => 'failed', 'value' => 'disk full']] as $error) {
        [$invocation] = auditFixture([ResourceValueCodec::handle('stashd:plugin/io-host.staging-area', 'area'), (object) ['error' => $error], null]);
        expect(fn() => (new Tools($invocation))->stage())->toThrow(HostFailure::class);
        $invocation->requireActive();
    }
});

it('rejects cancellation regression across two public processes', function (): void {
    [$invocation] = auditFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'first')],
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'second')],
        (object) ['invocation-cancel-requested' => true, 'state' => 'pending'],
        (object) ['invocation-cancel-requested' => false, 'state' => 'pending'],
    ]);
    $tools = new Tools($invocation);
    $first = $tools->start('approved');
    $second = $tools->start('approved');
    expect($first->poll(0)->cancelRequested)->toBeTrue();
    expect(fn() => $second->poll(0))->toThrow(ProtocolViolation::class);
    expect(fn() => $invocation->requireActive())->toThrow(ProtocolViolation::class);
});

it('rejects public activity counts wider than PHP integers', function (): void {
    [$invocation] = auditFixture([
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')],
        (object) ['invocation-cancel-requested' => false, 'state' => (object) ['tag' => 'event', 'value' => (object) ['tag' => 'stdout-activity', 'value' => '9223372036854775808']]],
    ]);
    $invocation->resources->accept('audit', 'writer', 'stashd:plugin/io-host.staged-writer');
    $process = (new Tools($invocation))->start('approved', output: new Writer(new RemoteStagedWriter($invocation, 'writer')));
    expect(fn() => $process->poll())->toThrow(ProtocolViolation::class);
});

it('transmits the exact prepared payload and rejects stale preparations', function (): void {
    [$invocation, $host, $output] = auditFixture([null]);
    $params = (object) ['self' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')];
    $prepared = $invocation->prepare('stashd:plugin/io-host.helper-process.cancel', $params);
    $invocation->call('stashd:plugin/io-host.helper-process.cancel', $params, prepared: $prepared);
    rewind($output);
    $bytes = stream_get_contents($output);
    expect(substr($bytes, 4))->toBe($prepared[1]);
    expect(fn() => $invocation->call('stashd:plugin/io-host.helper-process.cancel', $params, prepared: $prepared))->toThrow(ProtocolViolation::class);
});


it('rejects publication success without collection EOF and accepts typed failure', function (): void {
    $plugin = new /**
     * Attempts premature publication completion or explicit failure.
     */ class implements BroadcastPlugin {
        /**
         * Select whether this author returns a normal failure.
         */
        public bool $fail = false;
        /**
         * Return without enumerating to exercise completion enforcement.
         */
        public function publish(Publish $request): Publication
        {
            if ($this->fail) {
                throw new Failure('destination unavailable', true);
            }

            return new Publication();
        }
        /**
         * Supply unused action output.
         */
        public function action(Action $request): ActionResult
        {
            return new ActionResult();
        }
    };
    $params = (object) ['request' => (object) ['collection' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.item-collection', 'collection'), 'reporter' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.publication-reporter', 'reporter'), 'maximum-report-records-per-batch' => 2], 'configuration' => (object) ['settings' => []], 'credentials' => []];
    [$invocation] = auditFixture([]);
    $dispatcher = new BroadcastDispatcher();
    expect(fn() => $dispatcher->invoke($plugin, BroadcastPluginMethod::Publish, $params, $invocation))->toThrow(ProtocolViolation::class);
    [$invocation] = auditFixture([]);
    $plugin->fail = true;
    $result = $dispatcher->invoke($plugin, BroadcastPluginMethod::Publish, $params, $invocation);
    expect($result->error->tag)->toBe('failed')->and($result->error->value->retryable)->toBeTrue();
});

it('retains finished writer ownership until explicit close', function (): void {
    [$invocation] = auditFixture([(object) ['ok' => (object) ['reference' => 'receipt', 'media-type' => null, 'size-bytes' => '0', 'metadata' => []]], null]);
    $invocation->resources->accept('audit', 'writer', 'stashd:plugin/io-host.staged-writer');
    $writer = new Writer(new RemoteStagedWriter($invocation, 'writer'));
    $artifact = $writer->finish();
    $invocation->resources->requireOwned('audit', 'writer', 'stashd:plugin/io-host.staged-writer');
    $writer->close();
    expect($artifact->receipt()->reference)->toBe('receipt');
});


it('splits publication reports by encoded bytes and emits nothing for empty reports', function (): void {
    [$invocation, $host, $output] = auditFixture([(object) ['ok' => null], (object) ['ok' => null]]);

    foreach (['collection' => 'item-collection', 'reporter' => 'publication-reporter'] as $id => $type) {
        $invocation->resources->accept('audit', $id, 'stashd:plugin/broadcast-host.' . $type);
    }
    $collection = new RemoteHostResource($invocation, 'collection', 'stashd:plugin/broadcast-host.item-collection');
    $reporter = new RemoteHostResource($invocation, 'reporter', 'stashd:plugin/broadcast-host.publication-reporter');
    $request = new Publish($invocation, $collection, $reporter, 10, [], new Tools($invocation));
    $request->reportFiles([]);
    expect(ftell($output))->toBe(0);
    $paths = [str_repeat('a', 2300), str_repeat('b', 2300)];
    $request->reportFiles(array_map(static fn($path) => new PublishedFile(null, null, $path), $paths));
    rewind($output);

    foreach ($paths as $path) {
        $frame = $host->read();
        expect(count($frame->params->files))->toBe(1)->and($frame->params->files[0]->{'relative-path'})->toBe($path)->and(strlen(Json::encode($frame)))->toBeLessThanOrEqual(4096);
    }
    expect($host->read())->toBeNull();
});
