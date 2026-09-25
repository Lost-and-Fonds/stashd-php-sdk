<?php

declare(strict_types=1);

use Stashd\PluginSdk\AcquisitionResult;
use Stashd\PluginSdk\Artifact;
use Stashd\PluginSdk\ArtifactRole;
use Stashd\PluginSdk\Choice;
use Stashd\PluginSdk\DerivedArtifact;
use Stashd\PluginSdk\DiscoveredItem;
use Stashd\PluginSdk\Item;
use Stashd\PluginSdk\ItemResource;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\OperationResult;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\Preparation;
use Stashd\PluginSdk\Publication;
use Stashd\PluginSdk\PublishedFile;
use Stashd\PluginSdk\ResolvedInput;
use Stashd\PluginSdk\Setting;
use Stashd\PluginSdk\SourceDescriptor;
use Stashd\PluginSdk\StagedArtifact;
use Stashd\PluginSdk\UnavailableArtifact;
use Stashd\PluginSdk\WireMapper;

it('maps Input variants, nullable fields, integers, booleans, and artifact roles exactly', function (): void {
    $source = new SourceDescriptor([
        'enabled' => OptionValue::boolean(false),
        'count' => OptionValue::number(-9),
        'title' => OptionValue::text('雪'),
    ]);
    $sourceWire = [
        ['key' => 'enabled', 'value' => ['tag' => 'boolean', 'value' => false]],
        ['key' => 'count', 'value' => ['tag' => 'number', 'value' => -9]],
        ['key' => 'title', 'value' => ['tag' => 'text', 'value' => '雪']],
    ];
    expect(WireMapper::sourceDescriptor($source))->toBe($sourceWire);
    expect(WireMapper::sourceDescriptorFromWire($sourceWire)->values)->toEqual($source->values);

    $resolved = new ResolvedInput('input-7', 'canonical:7', 'video', null, 'art:7', 0, 0, false);
    expect(WireMapper::resolvedInput($resolved))->toBe([
        'id' => 'input-7',
        'canonical-reference' => 'canonical:7',
        'kind' => 'video',
        'title' => null,
        'artwork-reference' => 'art:7',
        'estimated-item-count' => 0,
        'size-bytes' => 0,
        'size-estimated' => false,
    ]);

    $discovered = new DiscoveredItem('item-1', 'source:1', 'Title', null, '2026-01-02T03:04:05Z', null, 0, 'audio', 0, false, null);
    $discoveredWire = [
        'id' => 'item-1',
        'reference' => 'source:1',
        'title' => 'Title',
        'description' => null,
        'published-at' => '2026-01-02T03:04:05Z',
        'artwork-reference' => null,
        'duration-seconds' => 0,
        'kind' => 'audio',
        'size-bytes' => 0,
        'size-estimated' => false,
        'upstream-state' => null,
    ];
    expect(WireMapper::discoveredItem($discovered))->toBe($discoveredWire);
    expect(WireMapper::discoveredItemFromWire($discoveredWire))->toEqual($discovered);

    $acquisition = new AcquisitionResult(
        [new StagedArtifact('artifact:1', null, 0, ArtifactRole::Primary->value)],
        [new UnavailableArtifact(ArtifactRole::Captions, true, 'not available')],
    );
    expect(WireMapper::acquisition($acquisition))->toBe([
        'artifacts' => [['reference' => 'artifact:1', 'role' => 'primary', 'media-type' => null, 'size-bytes' => 0]],
        'unavailable' => [['role' => 'captions', 'permanent' => true, 'message' => 'not available']],
    ]);

    foreach (PluginErrorCode::cases() as $code) {
        expect(WireMapper::pluginFailure(new PluginFailure($code, new PluginError('reason', true))))->toBe([
            'tag' => $code->value,
            'value' => ['message' => 'reason', 'retryable' => true],
        ]);
    }
});

it('round-trips Broadcast request records without SDK-only fields', function (): void {
    $wire = [
        'reference' => 'broadcast:1',
        'settings' => [['key' => 'enabled', 'value' => ['tag' => 'boolean', 'value' => false]]],
        'sources' => [['reference' => 'source:1', 'settings' => [['key' => 'count', 'value' => ['tag' => 'number', 'value' => -2]]]]],
        'items' => [[
            'id' => 'item:1',
            'source-reference' => null,
            'title' => 'A title',
            'description' => null,
            'published-at' => '2026-01-02T03:04:05Z',
            'duration-seconds' => 0,
            'resources' => [[
                'reference' => 'resource:1',
                'kind' => 'video',
                'derivation-key' => null,
                'url' => 'https://example.test/media',
                'media-type' => null,
                'size-bytes' => 0,
            ]],
        ]],
    ];

    $request = WireMapper::publishRequestFromWire($wire);
    expect(WireMapper::publishRequest($request))->toBe($wire);
    expect(array_keys(WireMapper::publishRequest($request)['items'][0]['resources'][0]))->toBe([
        'reference', 'kind', 'derivation-key', 'url', 'media-type', 'size-bytes',
    ]);
});

it('maps Broadcast result records, option values, and result errors to their WIT shapes', function (): void {
    expect(WireMapper::preparation(new Preparation([new DerivedArtifact('item:1', 'derived:1', 'source:1', 'preview', 'image', null, 0)])))->toBe([
        'artifacts' => [[
            'item-id' => 'item:1',
            'reference' => 'derived:1',
            'derived-from-reference' => 'source:1',
            'derivation-key' => 'preview',
            'kind' => 'image',
            'media-type' => null,
            'size-bytes' => 0,
        ]],
    ]);

    $publication = new Publication(new Artifact('artifact:1', null, 0), [new PublishedFile('item:1', 'source:1', 'media/item.bin')], [new Setting('published', OptionValue::text('雪'))]);
    $publicationWire = [
        'artifact' => ['reference' => 'artifact:1', 'media-type' => null, 'size-bytes' => 0],
        'files' => [['item-id' => 'item:1', 'source-reference' => 'source:1', 'relative-path' => 'media/item.bin']],
        'published-metadata' => [['key' => 'published', 'value' => ['tag' => 'text', 'value' => '雪']]],
    ];
    expect(WireMapper::publication($publication))->toBe($publicationWire);
    expect(WireMapper::publicationFromWire($publicationWire))->toEqual($publication);

    expect(WireMapper::operationResult(new OperationResult([new Choice('one', 'One')], [new Setting('flag', OptionValue::boolean(false)), new Setting('count', OptionValue::number(7)), new Setting('label', OptionValue::text('ok'))])))->toBe([
        'choices' => [['value' => 'one', 'label' => 'One']],
        'values' => [
            ['key' => 'flag', 'value' => ['tag' => 'boolean', 'value' => false]],
            ['key' => 'count', 'value' => ['tag' => 'number', 'value' => 7]],
            ['key' => 'label', 'value' => ['tag' => 'text', 'value' => 'ok']],
        ],
    ]);
});

it('rejects malformed required, optional, enum, list, and numeric WIT values', function (): void {
    expect(fn() => WireMapper::discoveredItemFromWire(['id' => 'i', 'reference' => 'r', 'title' => 't']))
        ->toThrow(\Stashd\PluginSdk\InvalidPluginResultException::class);
    expect(fn() => WireMapper::discoveredItemFromWire([
        'id' => 'i', 'reference' => 'r', 'title' => 't', 'description' => null, 'published-at' => null,
        'artwork-reference' => null, 'duration-seconds' => 4_294_967_296, 'kind' => null, 'size-bytes' => null,
        'size-estimated' => false, 'upstream-state' => null,
    ]))->toThrow(\Stashd\PluginSdk\InvalidPluginResultException::class);
    expect(fn() => WireMapper::resolvedInput(new ResolvedInput('i', estimatedItemCount: -1)))
        ->toThrow(\Stashd\PluginSdk\InvalidPluginResultException::class);
    expect(fn() => WireMapper::sourceDescriptorFromWire([['key' => 'bad', 'value' => ['tag' => 'number', 'value' => 1.5]]]))
        ->toThrow(\Stashd\PluginSdk\InvalidPluginResultException::class);
});
