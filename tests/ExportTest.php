<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Examples\ReferenceExporter;
use Stashd\PluginSdk\Runtime\Codec\ExportCodec;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ExportRunner;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

require_once __DIR__ . '/../examples/ReferenceExporter.php';

it('exports opaque references as canonical integer bytes', function (): void {
    $params = Json::decode('{"exporter":"references","collection":{"title":null,"entries":[{"reference":" A\\nB ","title":""}]},"options":[]}');
    $result = (new ExportCodec())->invoke(new ReferenceExporter(), $params);
    expect($result->ok->contents)->toBe(array_values(unpack('C*', "5: A\nB \n")));
});

it('accepts empty collections and distinguishes typed unsupported failures', function (): void {
    $params = Json::decode('{"exporter":"references","collection":{"title":"","entries":[]},"options":[]}');
    $codec = new ExportCodec();
    expect($codec->invoke(new ReferenceExporter(), $params)->ok->contents)->toBe([]);
    $params->exporter = 'other';
    expect($codec->invoke(new ReferenceExporter(), $params)->error->tag)->toBe('unsupported');
});

it('rejects legacy entry taxonomy and malformed option values', function (): void {
    $params = Json::decode('{"exporter":"references","collection":{"title":null,"entries":[{"reference":"x","title":null,"kind":"stash"}]},"options":[]}');
    expect(fn() => (new ExportCodec())->invoke(new ReferenceExporter(), $params))->toThrow(ProtocolViolation::class);
    $params->collection->entries = [];
    $params->options = [(object) ['key' => 'n', 'value' => (object) ['tag' => 'number', 'value' => 1]]];
    expect(fn() => (new ExportCodec())->invoke(new ReferenceExporter(), $params))->toThrow(ProtocolViolation::class);
});

it('performs plugin-first hello and returns a correlated lifecycle result', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $diagnostics = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write(Json::decode('{"protocol":1,"kind":"response","id":"hello","result":{"protocol":1,"min":1,"max":1,"max-frame-bytes":8192}}'));
    $host->write(Json::decode('{"protocol":1,"kind":"request","id":"host-1","invocation":"inv-1","method":"stashd:plugin/collection-export-plugin.export-collection","params":{"exporter":"references","collection":{"title":null,"entries":[]},"options":[]}}'));
    rewind($input);
    (new ExportRunner(new FrameChannel($input, $output), new Trace(TraceLevel::Ludicrous, $diagnostics)))->run(new ReferenceExporter(), 4096);
    rewind($output);
    expect($host->read()->method)->toBe('hello');
    $response = $host->read();
    expect($response->id)->toBe('host-1')->and($response->invocation)->toBe('inv-1')->and($response->result->ok->contents)->toBe([]);
    expect($host->read())->toBeNull();
    rewind($diagnostics);
    expect(stream_get_contents($diagnostics))->toContain('hello.accepted', 'invocation.start', 'cleanup');
    fclose($input);
    fclose($output);
    fclose($diagnostics);
});
