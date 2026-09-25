<?php

declare(strict_types=1);

use Stashd\PluginSdk\Tests\Support\RpcPeer;

it('dispatches collection export through the contract records and byte artifact', function (): void {
    $peer = RpcPeer::start('collection-export');

    try {
        $response = $peer->call('collection-export.export-collection', [
            'exporter' => 'default',
            'collection' => [
                'reference' => 'collection:1',
                'title' => 'Subscriptions',
                'entries' => [[
                    'title' => 'Feed name',
                    'kind' => 'podcast',
                    'label' => 'Podcast feed',
                    'reference' => 'https://example.test/feed.xml',
                ]],
            ],
            'options' => [
                ['key' => 'enabled', 'value' => ['tag' => 'boolean', 'value' => false]],
                ['key' => 'limit', 'value' => ['tag' => 'number', 'value' => -3]],
                ['key' => 'locale', 'value' => ['tag' => 'text', 'value' => '日本語']],
            ],
        ]);

        expect($response['result'])->toBe([
            'filename' => 'collection.bin',
            'media-type' => 'application/octet-stream',
            'contents' => [0, 195, 169, 255],
        ]);
        expect($peer->hostCalls())->toBe([[
            'protocol' => 1,
            'id' => 'sdk-1',
            'kind' => 'request',
            'method' => 'event.log',
            'params' => [
                'message' => '{"reference":"collection:1","title":"Subscriptions","entries":[["Feed name","podcast","Podcast feed","https://example.test/feed.xml"]],"options":[["enabled",{"tag":"boolean","value":false}],["limit",{"tag":"number","value":-3}],["locale",{"tag":"text","value":"日本語"}]]}',
            ],
        ]]);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('maps typed collection exporter failures and rejects malformed export requests', function (): void {
    $peer = RpcPeer::start('collection-export');

    try {
        $request = [
            'collection' => ['reference' => null, 'title' => null, 'entries' => []],
            'options' => [],
        ];

        expect($peer->call('collection-export.export-collection', ['exporter' => 'typed', ...$request])['error'])->toBe([
            'tag' => 'rate-limited',
            'value' => ['message' => 'try later', 'retryable' => true],
        ]);
        expect($peer->call('collection-export.export-collection', ['exporter' => 'panic', ...$request])['error'])->toBe([
            'tag' => 'failed',
            'value' => ['message' => 'unexpected', 'retryable' => false],
        ]);
        expect($peer->call('collection-export.export-collection', ['exporter' => 'default', 'collection' => [], 'options' => []])['error'])->toBe([
            'tag' => 'failed',
            'value' => ['message' => 'required collection field is missing: reference', 'retryable' => false],
        ]);
        expect($peer->hostCalls())->toBe([]);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});
