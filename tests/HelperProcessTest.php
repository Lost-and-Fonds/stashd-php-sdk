<?php

declare(strict_types=1);

use Stashd\PluginSdk\Contract\IoHost\HelperEventOutput;
use Stashd\PluginSdk\Contract\IoHost\HelperEventStdoutActivity;
use Stashd\PluginSdk\Contract\IoHost\HelperEventTerminal;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalExited;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\HelperProcessCall;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;

/**
 * Create an in-memory canonical host and plugin RPC channel for one helper invocation.
 */
function helperFixture(array $results, int $maximum = 4096): array
{
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $sink = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->negotiate($maximum, $maximum);

    foreach ($results as $index => $result) {
        $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-' . ($index + 1),
            'invocation' => 'inv', 'result' => $result]);
    }

    rewind($input);
    $plugin = new FrameChannel($input, $output);
    $plugin->negotiate($maximum, $maximum);
    $invocation = new Invocation('inv', 'host-1', $plugin, new Trace(TraceLevel::Ludicrous, $sink), ['stashd:plugin/io-host']);

    return [$invocation, $host, $input, $output, $sink];
}

/**
 * Convert canonical language-neutral vector events to their exact WIT wire forms.
 */
function helperEventWire(mixed $event): mixed
{
    if (isset($event['output'])) {
        $output = $event['output'];
        $bytes = isset($output['bytes_hex']) ? array_values(unpack('C*', hex2bin($output['bytes_hex']))) : range(0, 255);

        return (object) ['tag' => 'output', 'value' => (object) ['channel' => $output['channel'], 'bytes' => $bytes]];
    }

    if (isset($event['stdout-activity'])) {
        return (object) ['tag' => 'stdout-activity', 'value' => (string) $event['stdout-activity']];
    }

    $terminal = $event['terminal'];
    $value = is_string($terminal) ? $terminal : (object) ['tag' => array_key_first($terminal), 'value' => is_array(current($terminal)) ? (object) current($terminal) : current($terminal)];

    return (object) ['tag' => 'terminal', 'value' => $value];
}

it('consumes every canonical unstaged helper event sequence and keeps terminal then EOF', function (): void {
    $vectors = json_decode(file_get_contents(__DIR__ . '/../resources/contract/helper-process-vectors.json'), true, flags: JSON_THROW_ON_ERROR);

    foreach ($vectors['event_order'] as $vector) {
        if (!isset($vector['events']) || $vector['name'] === 'large-output-byte-sequence-segmented-to-fit-frame') {
            continue;
        }

        $events = array_map(helperEventWire(...), $vector['events']);
        $results = [(object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')]];
        array_push($results, ...$events);
        $results[] = null;
        [$invocation, $host, $input, $output, $sink] = helperFixture($results);
        $process = HelperProcessCall::start($invocation, 'approved', [], null, null, []);

        foreach ($events as $wire) {
            $event = $process->nextEvent();
            expect($event)->toBeInstanceOf($wire->tag === 'output' ? HelperEventOutput::class : HelperEventTerminal::class);

            if ($event instanceof HelperEventOutput) {
                expect($event->value->bytes)->toBe($wire->value->bytes);
            }
        }

        expect($process->nextEvent())->toBeNull()->and($process->nextEvent())->toBeNull();
        $invocation->cleanup();
        fclose($input);
        fclose($output);
        fclose($sink);
    }
});

it('returns only the transferred staged writer after the canonical staged terminal', function (): void {
    $vectors = json_decode(file_get_contents(__DIR__ . '/../resources/contract/helper-process-vectors.json'), true, flags: JSON_THROW_ON_ERROR);
    $events = array_map(helperEventWire(...), $vectors['staged_stdout']['events']);
    $results = [(object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')]];
    array_push($results, ...$events);
    $results[] = null;
    [$invocation, $host, $input, $output, $sink] = helperFixture($results);
    $invocation->resources->accept('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
    $writer = new RemoteStagedWriter($invocation, 'writer-1');
    $process = HelperProcessCall::start($invocation, 'approved', [], null, $writer, []);
    expect(fn() => $writer->close())->toThrow(ProtocolViolation::class);

    foreach ($events as $wire) {
        $event = $process->nextEvent();

        if ($wire->tag === 'stdout-activity') {
            expect($event)->toBeInstanceOf(HelperEventStdoutActivity::class)->and($event->value->decimal)->toBe($wire->value);
        }

        if ($wire->tag === 'terminal') {
            expect($event)->toBeInstanceOf(HelperEventTerminal::class)
                ->and($event->value)->toBeInstanceOf(HelperTerminalExited::class);
            $returned = $event->value->value->output;
            expect($returned)->toBeInstanceOf(RemoteStagedWriter::class);
            $invocation->resources->requireOwned('inv', 'writer-1', 'stashd:plugin/io-host.staged-writer');
        }
    }

    expect($process->nextEvent())->toBeNull();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});

it('measures the complete response envelope for the canonical segmented output vector', function (): void {
    $vectors = json_decode(file_get_contents(__DIR__ . '/../resources/contract/helper-process-vectors.json'), true, flags: JSON_THROW_ON_ERROR);
    $vector = current(array_filter($vectors['event_order'], static fn(array $candidate): bool => $candidate['name'] === 'large-output-byte-sequence-segmented-to-fit-frame'));
    $chunks = array_map(helperEventWire(...), $vector['events']);
    $results = [(object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', 'process')]];
    array_push($results, ...$chunks);
    $results[] = null;
    [$invocation, $host, $input, $output, $sink] = helperFixture($results, $vector['frame_max_bytes']);
    $process = HelperProcessCall::start($invocation, 'approved', [], null, null, []);
    $bytes = [];

    foreach ($chunks as $index => $chunk) {
        $envelope = (object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-' . ($index + 2),
            'invocation' => 'inv', 'result' => $chunk];
        expect(strlen(Json::encode($envelope)))->toBeLessThanOrEqual($vector['frame_max_bytes']);
        $event = $process->nextEvent();

        if ($event instanceof HelperEventOutput) {
            array_push($bytes, ...$event->value->bytes);
        }
    }

    expect($bytes)->toHaveCount($vector['logical_output_byte_count'])
        ->and($bytes)->toBe(array_merge(...array_fill(0, 20, range(0, 255))))
        ->and($process->nextEvent())->toBeNull();
    $invocation->cleanup();
    fclose($input);
    fclose($output);
    fclose($sink);
});
