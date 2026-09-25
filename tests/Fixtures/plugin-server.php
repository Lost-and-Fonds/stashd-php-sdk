<?php

declare(strict_types=1);

use Stashd\PluginSdk\AcquisitionOptions;
use Stashd\PluginSdk\AcquisitionResult;
use Stashd\PluginSdk\Artifact;
use Stashd\PluginSdk\ArtifactRole;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\CapabilityUnavailableException;
use Stashd\PluginSdk\Choice;
use Stashd\PluginSdk\DerivedArtifact;
use Stashd\PluginSdk\DiscoveredItem;
use Stashd\PluginSdk\DiscoveryIntent;
use Stashd\PluginSdk\ExportedFile;
use Stashd\PluginSdk\FinalizationRequest;
use Stashd\PluginSdk\HostCapabilityException;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\InputOption;
use Stashd\PluginSdk\Item;
use Stashd\PluginSdk\ItemResource;
use Stashd\PluginSdk\MediaKind;
use Stashd\PluginSdk\OperationRequest;
use Stashd\PluginSdk\OperationResult;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\PluginFailureException;
use Stashd\PluginSdk\PluginRegistry;
use Stashd\PluginSdk\Preparation;
use Stashd\PluginSdk\Publication;
use Stashd\PluginSdk\PublishRequest;
use Stashd\PluginSdk\ResolvedInput;
use Stashd\PluginSdk\Runtime\InputPluginServer;
use Stashd\PluginSdk\Runtime\PluginServer;
use Stashd\PluginSdk\Setting;
use Stashd\PluginSdk\SourceDescriptor;
use Stashd\PluginSdk\StagedArtifact;
use Stashd\PluginSdk\StashCollection;
use Stashd\PluginSdk\StashCollectionEntry;
use Stashd\PluginSdk\StashCollectionExporter;
use Stashd\PluginSdk\UnavailableArtifact;
use Stashd\PluginSdk\WireMapper;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$world = $argv[1] ?? '';

if ($world === 'input') {
    (new InputPluginServer(static fn(PluginContext $context): InputPlugin => new class ($context) implements InputPlugin {
        public function __construct(private PluginContext $context) {}

        public function resolve(SourceDescriptor $source): ResolvedInput
        {
            if ($source->text('exercise-host') === 'yes') {
                $response = $this->context->http->request('POST', 'https://example.test/雪', ['X-Test' => 'input'], "\x00é\xff", 'input-secret');
                $artifact = $this->context->staging->stage('input/file.bin', null);
                $helper = $this->context->helpers->run('probe', ['--', 'source://one']);
                $this->context->progress->report('resolve', 0.25);
                $this->context->progress->discovered(new DiscoveredItem('seen', 'source://seen', 'Seen'));
                $this->context->logger->log('input log');

                try {
                    $this->context->staging->write('blocked.bin', 'x');
                    $writeBlocked = false;
                } catch (CapabilityUnavailableException) {
                    $writeBlocked = true;
                }

                return new ResolvedInput(
                    'resolved',
                    $artifact->reference,
                    $writeBlocked ? 'write-blocked' : 'write-allowed',
                    bin2hex($response->body()),
                    $response->headers['content-type'],
                    $response->status,
                    $artifact->sizeBytes,
                    false,
                );
            }

            return new ResolvedInput(
                $source->text('id') ?? 'input',
                $source->text('canonical') ?? null,
                $source->values['kind']?->value ?? null,
                $source->values['title']?->value ?? null,
                null,
                0,
                0,
                false,
            );
        }

        public function discover(string $inputId, DiscoveryIntent $intent, array $options = []): array
        {
            if ($inputId === 'typed') {
                throw new PluginFailureException(new PluginFailure(PluginErrorCode::RateLimited, new PluginError('try later', true)));
            }

            if ($inputId === 'panic') {
                throw new RuntimeException('unexpected');
            }

            $value = implode('|', array_map(static fn(InputOption $option): string => $option->value->kind . ':' . (string) $option->value->value, $options));

            return [new DiscoveredItem($inputId, 'discovered:' . $intent->value, $value, null, '2026-01-02T03:04:05Z', null, 0, 'video', 0, false, null)];
        }

        public function acquire(DiscoveredItem $item, AcquisitionOptions $options): AcquisitionResult
        {
            $roles = $options->requestedRoles === null ? 'all' : implode(',', array_map(static fn(ArtifactRole $role): string => $role->value, $options->requestedRoles));
            $credentials = $options->credentials === null ? 'null' : ($options->credentials === [] ? 'empty' : implode(',', array_keys($options->credentials)));
            $message = $options->mediaKind->value . '|' . $roles . '|' . $credentials;

            return new AcquisitionResult(
                [new StagedArtifact('staged:' . $item->reference, null, 0, 'metadata')],
                [new UnavailableArtifact(ArtifactRole::Captions, true, $message)],
            );
        }
    }))->run();
}

if ($world === 'broadcast') {
    (new PluginServer(new class implements BroadcastPlugin {
        public function prepare(PublishRequest $request, PluginContext $context): Preparation
        {
            $item = $request->items[0];

            return new Preparation([new DerivedArtifact($item->id, 'derived:' . $item->resources[0]->reference, $item->resources[0]->reference, 'poster', 'image', null, 0)]);
        }

        public function publish(PublishRequest $request, PluginContext $context): Publication
        {
            return new Publication(new Artifact('publication:' . $request->reference, null, 0), [], [new Setting('published', OptionValue::boolean(false))]);
        }

        public function finalize(FinalizationRequest $request, PluginContext $context): Publication
        {
            return new Publication(new Artifact('final:' . $request->request->reference . ':' . $request->publication->artifact->reference, 'application/json', 1));
        }

        public function operation(OperationRequest $request, PluginContext $context): OperationResult
        {
            if ($request->name === 'capabilities') {
                $response = $context->http->request('POST', 'https://example.test/雪', ['X-Test' => 'broadcast'], "\x00é\xff", 'broadcast-secret');
                $written = $context->staging->write('catalog.json', "{}é", 'application/json');
                $staged = $context->staging->stage('media.bin', null);
                $helper = $context->helpers->run('pack', ['--level', '3']);
                $context->progress->report('publish', 0.5);
                $context->logger->log('broadcast log');

                return new OperationResult([], [
                    new Setting('http-body', OptionValue::text(bin2hex($response->body()))),
                    new Setting('http-header', OptionValue::text($response->headers['content-type'])),
                    new Setting('written', OptionValue::text($written->reference)),
                    new Setting('staged', OptionValue::text($staged->reference)),
                    new Setting('helper', OptionValue::text($helper->exitCode . ':' . $helper->stdout . ':' . $helper->stderr)),
                    new Setting('plugin-data', OptionValue::text($context->pluginDataPath)),
                    new Setting('staging-path', OptionValue::text($context->stagingPath ?? '')),
                ]);
            }

            if ($request->name === 'host-error') {
                try {
                    $context->http->request('GET', 'https://example.test/');
                } catch (HostCapabilityException $exception) {
                    return new OperationResult([], [
                        new Setting('method', OptionValue::text($exception->method)),
                        new Setting('tag', OptionValue::text($exception->tag)),
                        new Setting('value', OptionValue::text(is_string($exception->value) ? $exception->value : 'none')),
                    ]);
                }
            }

            if ($request->name === 'report-discovered') {
                $context->progress->discovered(new DiscoveredItem('id', 'ref', 'title'));
            }

            return new OperationResult([new Choice('yes', 'Yes')], [new Setting('enabled', OptionValue::boolean(false))]);
        }
    }))->run();
}

if ($world === 'collection-export') {
    $registry = new PluginRegistry();

    foreach (['default', 'typed', 'panic'] as $key) {
        $registry->collectionExporter($key, new class ($key) implements StashCollectionExporter {
            public function __construct(private string $id) {}

            public function key(): string
            {
                return $this->id;
            }

            public function label(): string
            {
                return $this->id;
            }

            public function export(StashCollection $collection, PluginContext $context): ExportedFile
            {
                if ($this->id === 'typed') {
                    throw new PluginFailureException(new PluginFailure(PluginErrorCode::RateLimited, new PluginError('try later', true)));
                }

                if ($this->id === 'panic') {
                    throw new RuntimeException('unexpected');
                }

                $message = json_encode([
                    'reference' => $collection->reference,
                    'title' => $collection->title,
                    'entries' => array_map(static fn(StashCollectionEntry $entry): array => [
                        $entry->stashName,
                        $entry->broadcastKey,
                        $entry->broadcastName,
                        $entry->publicUrl,
                    ], $collection->entries),
                    'options' => array_map(static fn(Setting $setting): array => [$setting->key, $setting->value->toWire()], $collection->options),
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if (! is_string($message)) {
                    throw new RuntimeException('could not encode collection export fixture input');
                }
                $context->logger->log($message);

                return new ExportedFile('collection.bin', 'application/octet-stream', "\x00é\xff");
            }
        });
    }

    (new PluginServer($registry))->run();
}

throw new RuntimeException('unknown fixture world');
