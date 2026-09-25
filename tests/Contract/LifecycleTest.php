<?php

declare(strict_types=1);

use Stashd\PluginSdk\Tests\Support\RpcPeer;

function inputPluginHostResult(array $message): array
{
    return match ($message['method'] ?? null) {
        'http.request' => ['status' => 206, 'headers' => [['name' => 'content-type', 'value' => 'application/octet-stream']], 'body' => [0, 195, 169, 255]],
        'staging.stage' => ['reference' => 'input:staged', 'role' => null, 'media-type' => null, 'size-bytes' => 7],
        'helper.run' => ['exit-code' => 0, 'stdout' => 'input out', 'stderr' => ''],
        'event.progress', 'event.discovered', 'event.log' => [],
        default => throw new RuntimeException('unexpected Input host call: ' . ($message['method'] ?? 'missing')),
    };
}

function broadcastPluginHostResult(array $message): array
{
    return match ($message['method'] ?? null) {
        'http.request' => ['status' => 206, 'headers' => [['name' => 'content-type', 'value' => 'application/octet-stream']], 'body' => [0, 195, 169, 255]],
        'staging.write' => ['reference' => 'broadcast:written', 'media-type' => 'application/json', 'size-bytes' => 4],
        'staging.stage' => ['reference' => 'broadcast:staged', 'media-type' => null, 'size-bytes' => 0],
        'helper.run' => ['exit-code' => 3, 'stdout' => 'partial', 'stderr' => 'warn'],
        'event.progress', 'event.log' => [],
        default => throw new RuntimeException('unexpected Broadcast host call: ' . ($message['method'] ?? 'missing')),
    };
}

it('dispatches Input resolve, both discover intents, and acquire using the WIT values', function (): void {
    $peer = RpcPeer::start('input');

    try {
        expect($peer->call('input.resolve', ['source' => [
            ['key' => 'id', 'value' => ['tag' => 'text', 'value' => 'input-7']],
            ['key' => 'canonical', 'value' => ['tag' => 'text', 'value' => 'canonical:7']],
            ['key' => 'kind', 'value' => ['tag' => 'text', 'value' => 'video']],
            ['key' => 'title', 'value' => ['tag' => 'text', 'value' => 'A title']],
        ]]))->toMatchArray(['result' => [
            'id' => 'input-7', 'canonical-reference' => 'canonical:7', 'kind' => 'video', 'title' => 'A title',
            'artwork-reference' => null, 'estimated-item-count' => 0, 'size-bytes' => 0, 'size-estimated' => false,
        ]]);

        $options = [
            ['key' => 'enabled', 'value' => ['tag' => 'boolean', 'value' => false]],
            ['key' => 'count', 'value' => ['tag' => 'number', 'value' => -9]],
            ['key' => 'label', 'value' => ['tag' => 'text', 'value' => '雪']],
        ];

        foreach (['refresh', 'complete'] as $intent) {
            expect($peer->call('input.discover', ['input-id' => 'input-7', 'intent' => $intent, 'options' => $options]))->toMatchArray(['result' => [[
                'id' => 'input-7',
                'reference' => 'discovered:' . $intent,
                'title' => 'boolean:|number:-9|text:雪',
                'description' => null,
                'published-at' => '2026-01-02T03:04:05Z',
                'artwork-reference' => null,
                'duration-seconds' => 0,
                'kind' => 'video',
                'size-bytes' => 0,
                'size-estimated' => false,
                'upstream-state' => null,
            ]]]);
        }

        $item = [
            'id' => 'item-1', 'reference' => 'source:1', 'title' => 'Item', 'description' => null, 'published-at' => null,
            'artwork-reference' => null, 'duration-seconds' => null, 'kind' => null, 'size-bytes' => null,
            'size-estimated' => false, 'upstream-state' => null,
        ];
        $acquired = $peer->call('input.acquire', ['item' => $item, 'options' => [
            'media-kind' => 'audio', 'options' => $options, 'requested-roles' => ['metadata'], 'credentials' => null,
        ]]);
        expect($acquired)->toMatchArray(['result' => [
            'artifacts' => [['reference' => 'staged:source:1', 'role' => 'metadata', 'media-type' => null, 'size-bytes' => 0]],
            'unavailable' => [['role' => 'captions', 'permanent' => true, 'message' => 'audio|metadata|null']],
        ]]);

        $emptyOptions = $peer->call('input.acquire', ['item' => $item, 'options' => [
            'media-kind' => 'video', 'options' => [], 'requested-roles' => null, 'credentials' => [],
        ]]);
        expect($emptyOptions['result']['unavailable'][0]['message'])->toBe('video|all|empty');
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('dispatches every Broadcast lifecycle method with exact contract records', function (): void {
    $peer = RpcPeer::start('broadcast');

    try {
        $request = [
            'reference' => 'broadcast:1',
            'settings' => [['key' => 'enabled', 'value' => ['tag' => 'boolean', 'value' => false]]],
            'sources' => [],
            'items' => [[
                'id' => 'item:1', 'source-reference' => null, 'title' => 'Title', 'description' => null,
                'published-at' => null, 'duration-seconds' => null,
                'resources' => [[
                    'reference' => 'media:1', 'kind' => 'video', 'derivation-key' => null, 'url' => null,
                    'media-type' => null, 'size-bytes' => 0,
                ]],
            ]],
        ];

        expect($peer->call('broadcast.prepare', $request)['result'])->toBe(['artifacts' => [[
            'item-id' => 'item:1', 'reference' => 'derived:media:1', 'derived-from-reference' => 'media:1',
            'derivation-key' => 'poster', 'kind' => 'image', 'media-type' => null, 'size-bytes' => 0,
        ]]]);

        $publication = [
            'artifact' => ['reference' => 'publication:broadcast:1', 'media-type' => null, 'size-bytes' => 0],
            'files' => [],
            'published-metadata' => [['key' => 'published', 'value' => ['tag' => 'boolean', 'value' => false]]],
        ];
        expect($peer->call('broadcast.publish', $request)['result'])->toBe($publication);
        expect($peer->call('broadcast.finalize', ['request' => $request, 'publication' => $publication])['result'])->toBe([
            'artifact' => ['reference' => 'final:broadcast:1:publication:broadcast:1', 'media-type' => 'application/json', 'size-bytes' => 1],
            'files' => [],
            'published-metadata' => [],
        ]);
        expect($peer->call('broadcast.operation', [
            'name' => 'choices',
            'settings' => [['key' => 'setting', 'value' => ['tag' => 'number', 'value' => 4]]],
            'payload' => [['key' => 'payload', 'value' => ['tag' => 'text', 'value' => '雪']]],
        ])['result'])->toBe([
            'choices' => [['value' => 'yes', 'label' => 'Yes']],
            'values' => [['key' => 'enabled', 'value' => ['tag' => 'boolean', 'value' => false]]],
        ]);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('maps capability requests and replies through the Broadcast RPC peer', function (): void {
    $peer = RpcPeer::start('broadcast', broadcastPluginHostResult(...));

    try {
        $response = $peer->call('broadcast.operation', ['name' => 'capabilities', 'settings' => [], 'payload' => []]);
        expect($response['result']['values'])->toBe([
            ['key' => 'http-body', 'value' => ['tag' => 'text', 'value' => '00c3a9ff']],
            ['key' => 'http-header', 'value' => ['tag' => 'text', 'value' => 'application/octet-stream']],
            ['key' => 'written', 'value' => ['tag' => 'text', 'value' => 'broadcast:written']],
            ['key' => 'staged', 'value' => ['tag' => 'text', 'value' => 'broadcast:staged']],
            ['key' => 'helper', 'value' => ['tag' => 'text', 'value' => '3:partial:warn']],
            ['key' => 'plugin-data', 'value' => ['tag' => 'text', 'value' => '/plugin-data']],
            ['key' => 'staging-path', 'value' => ['tag' => 'text', 'value' => '/staging']],
        ]);

        $calls = $peer->hostCalls();
        expect(array_column($calls, 'method'))->toBe(['http.request', 'staging.write', 'staging.stage', 'helper.run', 'event.progress', 'event.log']);
        expect($calls[0]['params'])->toBe([
            'method' => 'post', 'url' => 'https://example.test/雪',
            'headers' => [['name' => 'X-Test', 'value' => 'broadcast']],
            'body' => [0, 195, 169, 255], 'credential' => 'broadcast-secret',
        ]);
        expect($calls[1]['params'])->toBe(['relative-path' => 'catalog.json', 'content' => [123, 125, 195, 169], 'media-type' => 'application/json']);
        expect($calls[2]['params'])->toBe(['relative-path' => 'media.bin', 'media-type' => null]);
        expect($calls[3]['params'])->toBe(['name' => 'pack', 'args' => ['--level', '3']]);
        expect($calls[4]['params'])->toBe(['stage' => 'publish', 'fraction' => 0.5]);
        expect($calls[5]['params'])->toBe(['message' => 'broadcast log']);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('preserves tagged host result errors and their payloads', function (): void {
    $host = static function (array $message): array {
        if (($message['method'] ?? null) === 'http.request') {
            return ['__rpc_error' => ['tag' => 'unavailable', 'value' => 'maintenance']];
        }

        throw new RuntimeException('unexpected host call');
    };
    $peer = RpcPeer::start('broadcast', $host);

    try {
        expect($peer->call('broadcast.operation', ['name' => 'host-error', 'settings' => [], 'payload' => []])['result']['values'])->toBe([
            ['key' => 'method', 'value' => ['tag' => 'text', 'value' => 'http.request']],
            ['key' => 'tag', 'value' => ['tag' => 'text', 'value' => 'unavailable']],
            ['key' => 'value', 'value' => ['tag' => 'text', 'value' => 'maintenance']],
        ]);
        expect($peer->hostCalls()[0]['params'])->toBe([
            'method' => 'get', 'url' => 'https://example.test/', 'headers' => [], 'body' => [], 'credential' => null,
        ]);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('maps Input host capabilities and preserves world-specific availability', function (): void {
    $peer = RpcPeer::start('input', inputPluginHostResult(...));

    try {
        $response = $peer->call('input.resolve', ['source' => [['key' => 'exercise-host', 'value' => ['tag' => 'text', 'value' => 'yes']]]]);
        expect($response['result'])->toBe([
            'id' => 'resolved', 'canonical-reference' => 'input:staged', 'kind' => 'write-blocked',
            'title' => '00c3a9ff', 'artwork-reference' => 'application/octet-stream', 'estimated-item-count' => 206,
            'size-bytes' => 7, 'size-estimated' => false,
        ]);

        $calls = $peer->hostCalls();
        expect(array_column($calls, 'method'))->toBe(['http.request', 'staging.stage', 'helper.run', 'event.progress', 'event.discovered', 'event.log']);
        expect($calls[0]['params'])->toBe([
            'method' => 'post', 'url' => 'https://example.test/雪',
            'headers' => [['name' => 'X-Test', 'value' => 'input']], 'body' => [0, 195, 169, 255], 'credential' => 'input-secret',
        ]);
        expect($calls[1]['params'])->toBe(['relative-path' => 'input/file.bin', 'media-type' => null]);
        expect($calls[2]['params'])->toBe(['name' => 'probe', 'args' => ['--', 'source://one']]);
        expect($calls[3]['params'])->toBe(['progress' => ['stage' => 'resolve', 'fraction' => 0.25]]);
        expect($calls[4]['params'])->toBe(['item' => [
            'id' => 'seen', 'reference' => 'source://seen', 'title' => 'Seen', 'description' => null,
            'published-at' => null, 'artwork-reference' => null, 'duration-seconds' => null, 'kind' => null,
            'size-bytes' => null, 'size-estimated' => false, 'upstream-state' => null,
        ]]);
        expect($calls[5]['params'])->toBe(['message' => 'input log']);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('preserves typed failures and rejects malformed Input calls', function (): void {
    $peer = RpcPeer::start('input');

    try {
        expect($peer->call('input.discover', ['input-id' => 'typed', 'intent' => 'refresh', 'options' => []])['error'])->toBe([
            'tag' => 'rate-limited', 'value' => ['message' => 'try later', 'retryable' => true],
        ]);
        expect($peer->call('input.discover', ['input-id' => 'panic', 'intent' => 'refresh', 'options' => []])['error'])->toBe([
            'tag' => 'failed', 'value' => ['message' => 'unexpected', 'retryable' => false],
        ]);
        expect($peer->call('input.discover', ['intent' => 'refresh', 'options' => []])['error'])->toBe([
            'tag' => 'failed', 'value' => ['message' => 'required string field is missing or malformed: input-id', 'retryable' => false],
        ]);
        expect($peer->call('input.discover', ['input-id' => 'bad', 'intent' => 'refresh', 'options' => [['key' => 'x', 'value' => ['tag' => 'number', 'value' => 1.5]]]])['error']['tag'])->toBe('failed');
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});

it('reports unavailable Broadcast-only capability use as a typed unavailable plugin error', function (): void {
    $peer = RpcPeer::start('broadcast');

    try {
        expect($peer->call('broadcast.operation', ['name' => 'report-discovered', 'settings' => [], 'payload' => []])['error'])->toBe([
            'tag' => 'unavailable',
            'value' => ['message' => 'report-discovered is not available in this invocation.', 'retryable' => true],
        ]);
        expect($peer->hostCalls())->toBe([]);
        expect($peer->close())->toBe(0);
    } finally {
        $peer->close();
    }
});
