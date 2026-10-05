<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Input\Acquisition;
use Stashd\PluginSdk\Input\AcquisitionResult;
use Stashd\PluginSdk\Input\DiscoveredItem;
use Stashd\PluginSdk\Input\Discovery;
use Stashd\PluginSdk\Input\DiscoveryFinish;
use Stashd\PluginSdk\Input\Resolve;
use Stashd\PluginSdk\Input\ResolveDelegation;
use Stashd\PluginSdk\Input\ResolvedInput;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ExportRunner;

it('resolves and commits bounded discovery through the real runner', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $sink = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write(Json::decode('{"protocol":1,"kind":"response","id":"hello","result":{"protocol":1,"min":1,"max":1,"max-frame-bytes":8192}}'));
    $host->write(Json::decode('{"protocol":1,"kind":"request","id":"host-1","invocation":"one","method":"stashd:plugin/input-plugin.resolve","params":{"source":[{"key":"name","value":{"tag":"text","value":"catalog"}}],"credentials":[]}}'));
    $host->write(Json::decode('{"protocol":1,"kind":"request","id":"host-2","invocation":"two","method":"stashd:plugin/input-plugin.discover","params":{"request":{"input-id":"catalog","intent":"complete","options":[],"continuation":null,"refresh-state":null,"maximum-items-per-batch":1},"credentials":[]}}'));
    $host->negotiate(8192, 8192);
    $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-1', 'invocation' => 'two', 'result' => (object) ['ok' => null]]);
    rewind($input);
    $plugin = new /**
     * Implements the public Input methods without importing transport types.
     */ class implements InputPlugin {
        /**
         * Identify the source from a caller-supplied name.
         */
        public function resolve(Resolve $request): ResolvedInput
        {
            return new ResolvedInput($request->source()->text('name') ?? 'missing');
        }

        /**
         * Resolve a delegated reference independently.
         */
        public function resolveDelegation(ResolveDelegation $request): ResolvedInput
        {
            return new ResolvedInput($request->reference());
        }

        /**
         * Save one item and its final coverage atomically.
         */
        public function discover(Discovery $discovery): void
        {
            $discovery->commit([new DiscoveredItem('first', 'opaque')], finish: DiscoveryFinish::exhaustive('baseline'));
        }

        /**
         * Return an empty complete result for this test item.
         */
        public function acquire(Acquisition $acquisition): AcquisitionResult
        {
            return AcquisitionResult::complete();
        }
    };
    (new ExportRunner(new FrameChannel($input, $output), new Trace(TraceLevel::Off, $sink)))->run($plugin, 8192);
    rewind($output);
    expect($host->read()->method)->toBe('hello');
    $resolved = $host->read();
    $committed = $host->read();
    $discovered = $host->read();
    expect($resolved->result->ok->id)->toBe('catalog')
        ->and($committed->method)->toBe('stashd:plugin/input-host.commit-discovery-batch')
        ->and($committed->params->batch->items[0]->id)->toBe('first')
        ->and($committed->params->batch->progress->value->tag)->toBe('exhaustive')
        ->and($discovered->result->ok)->toBeNull();
    fclose($input);
    fclose($output);
    fclose($sink);
});
