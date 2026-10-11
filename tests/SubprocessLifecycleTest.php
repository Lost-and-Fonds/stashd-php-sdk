<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\Codec\FrameChannel;

it('exports a real public plugin in a subprocess over framed pipes', function (): void {
    $root = dirname(__DIR__);
    $script = 'require ' . var_export($root . '/vendor/autoload.php', true) . '; require ' . var_export($root . '/examples/ReferenceExporter.php', true) . '; (new Stashd\\PluginSdk\\Runtime\\ExportRunner(new Stashd\\PluginSdk\\Runtime\\Codec\\FrameChannel(STDIN, STDOUT), new Stashd\\PluginSdk\\Diagnostics\\Trace(Stashd\\PluginSdk\\Diagnostics\\TraceLevel::Off, STDERR)))->run(new Stashd\\PluginSdk\\Examples\\ReferenceExporter(), 4096);';
    $process = proc_open([PHP_BINARY, '-r', $script], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    expect(is_resource($process))->toBeTrue();
    stream_set_timeout($pipes[1], 5);
    $host = new FrameChannel($pipes[1], $pipes[0]);

    try {
        expect($host->read()->method)->toBe('hello');
        $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'hello', 'result' => (object) ['protocol' => 1, 'min' => 1, 'max' => 1, 'max-frame-bytes' => 4096]]);
        $host->write((object) ['protocol' => 1, 'kind' => 'request', 'id' => 'export', 'invocation' => 'subprocess', 'method' => 'stashd:plugin/collection-export-plugin.export-collection', 'params' => (object) ['exporter' => 'references', 'collection' => (object) ['title' => null, 'entries' => [(object) ['reference' => "opaque\nvalue", 'title' => null]]], 'options' => []]]);
        $response = $host->read();
        expect($response->id)->toBe('export')->and(pack('C*', ...$response->result->ok->contents))->toBe("12:opaque\nvalue\n");
        fclose($pipes[0]);
        expect($host->read())->toBeNull();
        expect(stream_get_contents($pipes[2]))->toBe('');
    } finally {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_terminate($process);
        proc_close($process);
    }
});
