<?php

declare(strict_types=1);

foreach (['BroadcastPlugin.php', 'InputPlugin.php', 'Logger.php', 'ProgressReporter.php', 'ReadableResource.php', 'HttpClient.php', 'StagingArea.php', 'HelperRunner.php'] as $interface) {
    require_once dirname(__DIR__, 2) . '/src/' . $interface;
}

foreach (glob(dirname(__DIR__, 2) . '/src/*.php') ?: [] as $file) {
    if (! in_array(basename($file), ['BroadcastPlugin.php', 'InputPlugin.php', 'Logger.php', 'ProgressReporter.php', 'ReadableResource.php', 'HttpClient.php', 'StagingArea.php'], true)) {
        require_once $file;
    }
}

use Stashd\PluginSdk\InvalidPluginResultException;
use Stashd\PluginSdk\AcquisitionResult;
use Stashd\PluginSdk\AcquisitionOptions;
use Stashd\PluginSdk\ArtifactRole;
use Stashd\PluginSdk\DiscoveryIntent;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\DiscoveredItem;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\PluginFailureException;
use Stashd\PluginSdk\PluginInvoker;
use Stashd\PluginSdk\PublishRequest;
use Stashd\PluginSdk\Item;
use Stashd\PluginSdk\ItemResource;
use Stashd\PluginSdk\NullLogger;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\ResolvedInput;
use Stashd\PluginSdk\SourceDescriptor;
use Stashd\PluginSdk\StagedArtifact;
use Stashd\PluginSdk\UnavailableArtifact;
use Stashd\PluginSdk\WireMapper;
use Stashd\PluginSdk\Runtime\RuntimeProgressReporter;
use Stashd\PluginSdk\Runtime\InputPluginServer;

it('passes the SDK conformance checks', function (): void {

    if (OptionValue::text('fixture')->toWire() !== ['tag' => 'text', 'value' => 'fixture']) {
        throw new RuntimeException('SDK option mapping failed');
    }
    $failure = new PluginFailure(PluginErrorCode::Unavailable, new PluginError('temporary fixture failure', true));
    $wireFailure = WireMapper::pluginFailure($failure);

    if (! is_array($wireFailure['value'] ?? null) || ($wireFailure['value']['retryable'] ?? null) !== true) {
        throw new RuntimeException('SDK retryability mapping failed');
    }

    expect(WireMapper::acquisition(new AcquisitionResult(unavailable: [new UnavailableArtifact(ArtifactRole::Captions, true, 'No creator captions')])))->toBe([
        'artifacts' => [],
        'unavailable' => [['role' => 'captions', 'permanent' => true, 'message' => 'No creator captions']],
    ]);
    expect(WireMapper::stagedArtifact(new StagedArtifact('video.en.vtt', 'text/vtt', 62, 'captions', 'en')))->toBe([
        'reference' => 'video.en.vtt',
        'media-type' => 'text/vtt',
        'size-bytes' => 62,
        'role' => 'captions',
        'language' => 'en',
    ]);
    expect(WireMapper::stagedArtifact(new StagedArtifact('video.mp4', 'video/mp4'))['language'])->toBeNull();

    $request = new PublishRequest('broadcast-1', [], [], [
        new Item('item-1', 'Item', [new ItemResource('caption.en.vtt', 'subtitle', language: 'en')]),
    ]);
    $mapped = WireMapper::publishRequest($request);
    expect($mapped['items'][0]['resources'][0]['language'])->toBe('en')
        ->and(WireMapper::publishRequestFromWire($mapped)->items[0]->resources[0]->language)->toBe('en');
    expect(WireMapper::publishRequestFromWire(WireMapper::publishRequest(new PublishRequest('broadcast-1', [], [], [
        new Item('item-1', 'Item', [new ItemResource('video.mp4', 'video')]),
    ])))->items[0]->resources[0]->language)->toBeNull();

    foreach (PluginErrorCode::cases() as $code) {
        $mapped = WireMapper::pluginFailure(new PluginFailure($code, new PluginError('fixture', false)));

        if (($mapped['tag'] ?? null) !== $code->value) {
            throw new RuntimeException('SDK error variant mapping failed for ' . $code->value);
        }
    }

    try {
        throw new PluginFailureException($failure);
    } catch (PluginFailureException $exception) {
        if (! $exception->failure->error->retryable) {
            throw new RuntimeException('SDK typed error failed');
        }
    }

    try {
        PluginInvoker::publish(static fn(PublishRequest $request): mixed => 'invalid', new PublishRequest('fixture'));

        throw new RuntimeException('invalid SDK result was accepted');
    } catch (InvalidPluginResultException) {
    }
    expect(fn(): mixed => WireMapper::publishRequestFromWire(['settings' => [], 'sources' => [], 'items' => []]))
        ->toThrow(InvalidPluginResultException::class);
    expect(fn(): mixed => OptionValue::fromWire(['tag' => 'number', 'value' => '7']))
        ->toThrow(InvalidPluginResultException::class);
    expect(fn(): mixed => WireMapper::discoveredItemFromWire(['id' => 'id', 'reference' => 'ref', 'title' => 'title', 'duration-seconds' => 'bad']))
        ->toThrow(InvalidPluginResultException::class);
    expect(WireMapper::discoveredItem(new DiscoveredItem('id', 'ref', 'title')))->toMatchArray(['id' => 'id', 'reference' => 'ref', 'title' => 'title']);
    $contextReflection = new ReflectionClass(PluginContext::class);
    $contextSource = (string) file_get_contents((string) $contextReflection->getFileName());

    if (str_contains($contextSource, 'bubblewrap') || str_contains($contextSource, 'FrameCodec')) {
        throw new RuntimeException('sandbox/RPC mechanics leaked into SDK context');
    }
    expect((new PluginContext(new NullLogger()))->pluginDataPath)->toBe('/plugin-data')
        ->and((new PluginContext(new NullLogger(), pluginDataPath: '/private-data'))->pluginDataPath)->toBe('/private-data')
        ->and((new PluginContext(new NullLogger(), stagingPath: '/staging'))->stagingPath)->toBe('/staging');
    $received = null;
    $plugin = new class (static function (AcquisitionOptions $options) use (&$received): void {
        $received = $options;
    }) implements InputPlugin {
        public function __construct(private Closure $capture) {}

        public function resolve(SourceDescriptor $source): ResolvedInput
        {
            throw new RuntimeException('unused');
        }

        public function discover(string $inputId, DiscoveryIntent $intent, array $options = []): array
        {
            throw new RuntimeException('unused');
        }

        public function acquire(DiscoveredItem $item, AcquisitionOptions $options): AcquisitionResult
        {
            ($this->capture)($options);

            return new AcquisitionResult();
        }
    };
    $server = new InputPluginServer(static fn(): never => throw new RuntimeException('unused'));
    $dispatch = new ReflectionMethod(InputPluginServer::class, 'dispatch');
    $dispatch->invoke($server, $plugin, 'input.acquire', [
        'item' => ['id' => 'video', 'reference' => 'https://youtube.com/watch?v=video', 'title' => 'Video'],
        'media_kind' => 'video',
        'credentials' => [
            ['key' => 'youtube-cookies', 'value' => "cookie-line\n"],
            ['key' => 'youtube-po-token', 'value' => 'fixture-token'],
        ],
    ]);
    expect($received)->toBeInstanceOf(AcquisitionOptions::class)
        ->and($received->credentials)->toBe(['youtube-cookies' => "cookie-line\n", 'youtube-po-token' => 'fixture-token'])
        ->and(fn() => $dispatch->invoke($server, $plugin, 'input.acquire', [
            'item' => ['id' => 'video', 'reference' => 'https://youtube.com/watch?v=video', 'title' => 'Video'],
            'media_kind' => 'video',
            'credentials' => [['key' => 'bad']],
        ]))->toThrow(InvalidPluginResultException::class);
    $progressEvents = [];
    $progress = new RuntimeProgressReporter(static function (string $method, array $params) use (&$progressEvents): void {
        $progressEvents[] = [$method, $params];
    });
    $progress->report('Downloading', 0.5, 1234, true);
    $progress->report('Downloading', 0.75, 2468);
    expect($progressEvents)->toBe([
        ['event.progress', ['stage' => 'Downloading', 'fraction' => 0.5, 'size_bytes' => 1234, 'size_estimated' => true]],
        ['event.progress', ['stage' => 'Downloading', 'fraction' => 0.75, 'size_bytes' => 2468, 'size_estimated' => false]],
    ]);
    expect(true)->toBeTrue();
});
