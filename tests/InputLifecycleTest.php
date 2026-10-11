<?php

declare(strict_types=1);

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Input\Acquisition;
use Stashd\PluginSdk\Input\AcquisitionResult;
use Stashd\PluginSdk\Input\DiscoveredItem;
use Stashd\PluginSdk\Input\Discovery;
use Stashd\PluginSdk\Input\DiscoveryFinish;
use Stashd\PluginSdk\Input\ErrorKind;
use Stashd\PluginSdk\Input\Failure;
use Stashd\PluginSdk\Input\Resolve;
use Stashd\PluginSdk\Input\ResolvedInput;
use Stashd\PluginSdk\Input\Source;
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

    foreach ([['probe-yes', 'opaque-source'], ['probe-no', null]] as [$identity, $reference]) {
        $host->write((object) ['protocol' => 1, 'kind' => 'request', 'id' => $identity, 'invocation' => $identity,
            'method' => 'stashd:plugin/input-plugin.can-resolve', 'params' => (object) ['source' => (object) ['reference' => $reference, 'values' => []]]]);
    }
    $host->write(Json::decode('{"protocol":1,"kind":"request","id":"host-1","invocation":"one","method":"stashd:plugin/input-plugin.resolve","params":{"source":{"reference":null,"values":[{"key":"name","value":{"tag":"text","value":"catalog"}}]},"credentials":[]}}'));

    foreach (['direct', 'discovered', 'failed'] as $origin) {
        $host->write((object) ['protocol' => 1, 'kind' => 'request', 'id' => $origin, 'invocation' => $origin,
            'method' => 'stashd:plugin/input-plugin.resolve', 'params' => (object) ['source' => (object) ['reference' => $origin === 'failed' ? 'unsupported-source' : 'opaque-source', 'values' => []], 'credentials' => []]]);
    }
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
            if ($request->source->reference === 'unsupported-source') {
                throw new Failure(ErrorKind::Unsupported, 'Source changed');
            }

            expect($request->tools()->credentials)->toBe([]);

            return new ResolvedInput($request->source->reference ?? $request->source->text('name') ?? 'missing');
        }

        /**
         * Recognize supported source contents without host calls.
         */
        public function canResolve(Source $source): bool
        {
            return $source->reference !== null || $source->text('name') !== null;
        }

        /**
         * Save one item and its final coverage atomically.
         */
        public function discover(Discovery $discovery): void
        {
            $discovery->commit([new DiscoveredItem('first', 'opaque', source: new Source('opaque-source'))], finish: DiscoveryFinish::exhaustive('baseline'));
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
    expect($host->read()->result)->toBeTrue()
        ->and($host->read()->result)->toBeFalse();
    $resolved = $host->read();
    $direct = $host->read();
    $crossInput = $host->read();
    expect($direct->result->ok->id)->toBe('opaque-source')
        ->and($crossInput->result)->toEqual($direct->result);
    expect($host->read()->result->error->tag)->toBe('unsupported');
    $committed = $host->read();
    $discovered = $host->read();
    expect($resolved->result->ok->id)->toBe('catalog')
        ->and($committed->method)->toBe('stashd:plugin/input-host.commit-discovery-batch')
        ->and($committed->params->batch->items[0]->id)->toBe('first')
        ->and($committed->params->batch->items[0]->source->reference)->toBe('opaque-source')
        ->and($committed->params->batch->items[0]->source->values)->toBe([])
        ->and($committed->params->batch->progress->value->tag)->toBe('exhaustive')
        ->and($discovered->result->ok)->toBeNull();
    fclose($input);
    fclose($output);
    fclose($sink);
});
