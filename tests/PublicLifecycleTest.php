<?php

declare(strict_types=1);

use Stashd\PluginSdk\Broadcast\Action;
use Stashd\PluginSdk\Broadcast\ActionResult;
use Stashd\PluginSdk\Broadcast\Publication;
use Stashd\PluginSdk\Broadcast\Publish;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Enrichment;
use Stashd\PluginSdk\EnrichmentPlugin;
use Stashd\PluginSdk\Input;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\ExportRunner;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Tools;

/**
 * Execute an author component through framed lifecycle requests.
 */
function publicLifecycle(object $plugin, string $method, object $params, array $results): array
{
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'hello', 'result' => (object) ['protocol' => 1, 'min' => 1, 'max' => 1, 'max-frame-bytes' => 4096]]);
    $host->write((object) ['protocol' => 1, 'kind' => 'request', 'id' => 'lifecycle', 'invocation' => 'public', 'method' => $method, 'params' => $params]);

    foreach ($results as $index => $result) {
        $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-' . ($index + 1), 'invocation' => 'public', 'result' => $result]);
    }
    rewind($input);
    (new ExportRunner(new FrameChannel($input, $output), new Trace(TraceLevel::Off, fopen('php://memory', 'w+b'))))->run($plugin, 4096);
    rewind($output);
    $frames = [];

    while (($frame = $host->read()) !== null) {
        $frames[] = $frame;
    }

    return $frames;
}

/**
 * Supply canonical staging responses without consuming finished writer ownership.
 */
function publicStagingResponses(): array
{
    return [ResourceValueCodec::handle('stashd:plugin/io-host.staging-area', 'area'),
        (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.staged-writer', 'writer')], null,
        (object) ['ok' => null],
        (object) ['ok' => (object) ['reference' => 'receipt', 'media-type' => 'application/octet-stream', 'size-bytes' => '3', 'metadata' => []]], null];
}

it('lets an input author acquire and return real staged bytes through the runner', function (): void {
    $plugin = new /**
     * Implements a normal author lifecycle using only public domain types.
     */ class implements InputPlugin {
        /**
         * Exercise the public canResolve lifecycle.
         */
        public function canResolve(Input\Source $source): bool
        {
            return true;
        }
        /**
         * Exercise the public resolve lifecycle.
         */
        public function resolve(Input\Resolve $request): Input\ResolvedInput
        {
            return new Input\ResolvedInput('input');
        }
        /**
         * Exercise the public discover lifecycle.
         */
        public function discover(Input\Discovery $request): void
        {
            $request->commit([], finish: Input\DiscoveryFinish::exhaustive());
        }
        /**
         * Exercise the public acquire lifecycle.
         */
        public function acquire(Input\Acquisition $request): Input\AcquisitionResult
        {
            expect($request->item->reference)->toBe('acquire');
            $writer = $request->tools()->stage('application/octet-stream');
            $writer->write("\x00\xffA");
            $artifact = $writer->finish();
            $writer->close();

            return Input\AcquisitionResult::complete($artifact);
        }
    };
    $frames = publicLifecycle($plugin, 'stashd:plugin/input-plugin.acquire', (object) ['item' => (object) ['id' => 'item', 'reference' => 'acquire', 'source' => null, 'size-bytes' => null, 'size-estimated' => false, 'metadata' => []], 'options' => (object) ['options' => [], 'credentials' => []]], publicStagingResponses());
    expect($frames[4]->params->bytes)->toBe([0, 255, 65])
        ->and(end($frames)->result->ok->artifacts[0]->reference)->toBe('receipt');
});

it('lets an enrichment author advertise options and return a derived artifact', function (): void {
    $plugin = new /**
     * Implements a normal author lifecycle using only public domain types.
     */ class implements EnrichmentPlugin {
        /**
         * Exercise the public capabilities lifecycle.
         */
        public function capabilities(Enrichment\Item $item): array
        {
            return [new Enrichment\Capability('derive', '1', [new Enrichment\ConfigurationOption('mode', 'Mode', true, [new Enrichment\ConfigurationChoice('fast', 'Fast')])])];
        }
        /**
         * Exercise the public enrich lifecycle.
         */
        public function enrich(Enrichment\Request $request): Enrichment\Result
        {
            expect($request->configuration[0]->value)->toBe('fast');
            $writer = $request->tools()->stage('application/octet-stream');
            $writer->write('new');
            $artifact = $writer->finish();
            $writer->close();

            return new Enrichment\Result(assets: [new Enrichment\DerivedAsset($artifact, [], 'derive', '1')]);
        }
    };
    $context = (object) ['item-id' => 'item', 'assets' => [], 'metadata' => []];
    $capabilities = publicLifecycle($plugin, 'stashd:plugin/enrichment-plugin.capabilities', (object) ['context' => $context], []);
    expect(end($capabilities)->result->ok[0]->options[0]->choices[0]->value)->toBe('fast');
    $frames = publicLifecycle($plugin, 'stashd:plugin/enrichment-plugin.enrich', (object) ['context' => $context, 'capability-id' => 'derive', 'capability-revision' => '1', 'configuration' => [(object) ['key' => 'mode', 'value' => 'fast']], 'credentials' => []], publicStagingResponses());
    expect($frames[4]->params->bytes)->toBe([110, 101, 119])
        ->and(end($frames)->result->ok->assets[0]->artifact->reference)->toBe('receipt')
        ->and(end($frames)->result->ok->assets[0]->activity)->toBe('derive');
});


it('lets a publisher enumerate nonempty public items through EOF', function (): void {
    $plugin = new /**
     * Enumerates public saved items without touching contract classes.
     */ class implements BroadcastPlugin {
        /**
         * Read every selected item before returning publication success.
         */
        public function publish(Publish $request): Publication
        {
            $ids = [];

            foreach ($request->items() as $item) {
                $ids[] = $item->id;
                expect($item->assets[0]->sizeBytes)->toBe(3);
                expect($item->metadata[0]->schema)->toBe('test');
                expect(implode('', iterator_to_array($request->openAsset($item->assets[0])->chunks())))->toBe('abc');
            }
            expect($ids)->toBe(['saved']);

            return new Publication();
        }
        /**
         * Return unused interactive action choices.
         */
        public function action(Action $request): ActionResult
        {
            return new ActionResult();
        }
    };
    $params = (object) ['request' => (object) ['collection' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.item-collection', 'collection'), 'reporter' => ResourceValueCodec::handle('stashd:plugin/broadcast-host.publication-reporter', 'reporter'), 'maximum-report-records-per-batch' => 2], 'configuration' => (object) ['settings' => []], 'credentials' => []];
    $item = (object) ['id' => 'saved', 'assets' => [(object) ['id' => 'asset', 'reference' => 'opaque', 'media-type' => null, 'size-bytes' => '3', 'metadata' => []]], 'metadata' => [(object) ['schema' => 'test', 'json' => '{}']]];
    $frames = publicLifecycle($plugin, 'stashd:plugin/broadcast-plugin.publish', $params, [(object) ['ok' => [$item]], (object) ['ok' => ResourceValueCodec::handle('stashd:plugin/io-host.byte-stream', 'stream')], (object) ['ok' => [97, 98, 99]], (object) ['ok' => null], null, (object) ['ok' => null]]);
    expect(end($frames)->result->ok->files)->toBe('not-applicable')->and($frames[1]->params->{'max-items'})->toBe(32);
});


it('rejects discovery failure after acknowledged terminal completion', function (): void {
    $plugin = new /**
     * Exercises terminal discovery acknowledgement followed by author failure.
     */ class implements InputPlugin {
        /**
         * Accept this test source without host calls.
         */
        public function canResolve(Input\Source $source): bool
        {
            return true;
        }
        /**
         * Return an unused resolved identity.
         */
        public function resolve(Input\Resolve $request): Input\ResolvedInput
        {
            return new Input\ResolvedInput('input');
        }
        /**
         * Attempt a contradictory typed failure after completion.
         */
        public function discover(Input\Discovery $request): void
        {
            expect($request->tools())->toBeInstanceOf(Tools::class);
            $request->commit([], finish: Input\DiscoveryFinish::exhaustive());

            throw new Input\Failure(Input\ErrorKind::Failed, 'too late');
        }
        /**
         * Return an unused acquisition result.
         */
        public function acquire(Input\Acquisition $request): Input\AcquisitionResult
        {
            return Input\AcquisitionResult::complete();
        }
    };
    $params = (object) ['request' => (object) ['input-id' => 'input', 'intent' => 'complete', 'options' => [], 'continuation' => null, 'refresh-state' => null, 'maximum-items-per-batch' => 1], 'credentials' => []];
    expect(fn() => publicLifecycle($plugin, 'stashd:plugin/input-plugin.discover', $params, [(object) ['ok' => null]]))->toThrow(ProtocolViolation::class, 'after acknowledged completion');
});
