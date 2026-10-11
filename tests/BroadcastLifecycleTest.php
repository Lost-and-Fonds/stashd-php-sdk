<?php

declare(strict_types=1);

use Stashd\PluginSdk\Broadcast\Action;
use Stashd\PluginSdk\Broadcast\ActionResult;
use Stashd\PluginSdk\Broadcast\Publication;
use Stashd\PluginSdk\Broadcast\Publish;
use Stashd\PluginSdk\Broadcast\PublishedFile;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ExportRunner;
use Stashd\PluginSdk\Shared\Metadata;

it('publishes every file and metadata record in bounded batches through the real runner', function (): void {
    $input = fopen('php://memory', 'w+b');
    $output = fopen('php://memory', 'w+b');
    $sink = fopen('php://memory', 'w+b');
    $host = new FrameChannel($output, $input);
    $host->write(Json::decode('{"protocol":1,"kind":"response","id":"hello","result":{"protocol":1,"min":1,"max":1,"max-frame-bytes":8192}}'));
    $host->write(Json::decode('{"protocol":1,"kind":"request","id":"publish","invocation":"publication","method":"stashd:plugin/broadcast-plugin.publish","params":{"request":{"collection":{"$resource":{"type":"stashd:plugin/broadcast-host.item-collection","id":"collection"}},"reporter":{"$resource":{"type":"stashd:plugin/broadcast-host.publication-reporter","id":"reporter"}},"maximum-report-records-per-batch":2},"configuration":{"settings":[]},"credentials":[]}}'));
    $host->negotiate(8192, 8192);

    for ($id = 1; $id <= 5; ++$id) {
        $host->write((object) ['protocol' => 1, 'kind' => 'response', 'id' => 'plugin-' . $id,
            'invocation' => 'publication', 'result' => (object) ['ok' => null]]);
    }

    rewind($input);
    $plugin = new /**
     * Reports a complete destination without knowing the host's batch limit.
     */ class implements BroadcastPlugin {
        /**
         * Report all destination records, including empty optional lists.
         */
        public function publish(Publish $request): Publication
        {
            $request->reportFiles(array_map(static fn(string $path) => new PublishedFile(null, null, $path), ['one', 'two', 'three']));
            $request->reportDestinationMetadata(array_map(static fn(string $schema) => new Metadata($schema, '{}'), ['first', 'second', 'third']));
            $request->reportFiles([]);
            $request->reportDestinationMetadata([]);

            foreach ($request->items() as $item) {
                throw new LogicException('Expected empty collection');
            }

            return new Publication(filesComplete: true);
        }

        /**
         * Return an empty result for unused interactive actions.
         */
        public function action(Action $request): ActionResult
        {
            return new ActionResult();
        }
    };

    try {
        (new ExportRunner(new FrameChannel($input, $output), new Trace(TraceLevel::Off, $sink)))->run($plugin, 8192);
    } finally {
        rewind($output);
        expect($host->read()->method)->toBe('hello');
        $files = [];
        $metadata = [];

        foreach ([['files', 2], ['files', 1], ['metadata', 2], ['metadata', 1]] as [$field, $count]) {
            $batch = $host->read();
            expect($batch->method)->toBe('stashd:plugin/broadcast-host.publication-reporter.' . ($field === 'files' ? 'report-files' : 'report-destination-metadata'))
                ->and($batch->params->self->{'$resource'}->id)->toBe('reporter')
                ->and($batch->params->{$field})->toHaveCount($count);

            if ($field === 'files') {
                array_push($files, ...array_column($batch->params->files, 'relative-path'));
            } else {
                array_push($metadata, ...array_column($batch->params->metadata, 'schema'));
            }
        }

        expect($files)->toBe(['one', 'two', 'three'])
            ->and($metadata)->toBe(['first', 'second', 'third'])
            ->and($host->read()->method)->toBe('stashd:plugin/broadcast-host.item-collection.next')
            ->and($host->read()->result->ok->files)->toBe('complete')
            ->and($host->read())->toBeNull();
        fclose($input);
        fclose($output);
        fclose($sink);
    }
});
