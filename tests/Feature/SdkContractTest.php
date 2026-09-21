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
use Stashd\PluginSdk\ArtifactRole;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\DiscoveredItem;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\PluginFailureException;
use Stashd\PluginSdk\PluginInvoker;
use Stashd\PluginSdk\PublishRequest;
use Stashd\PluginSdk\Item;
use Stashd\PluginSdk\ItemResource;
use Stashd\PluginSdk\StagedArtifact;
use Stashd\PluginSdk\UnavailableArtifact;
use Stashd\PluginSdk\WireMapper;

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
    expect(true)->toBeTrue();
});
