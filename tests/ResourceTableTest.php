<?php

declare(strict_types=1);

use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\ResourceTable;

it('expires borrows without consuming ownership', function (): void {
    $table = new ResourceTable('inv');
    $table->accept('inv', 'writer', 'writer-type');
    $table->borrow('inv', 'writer', 'writer-type', 'helper');
    $table->requireBorrow('inv', 'writer', 'writer-type', 'helper');
    expect(fn() => $table->drop('inv', 'writer', 'writer-type'))->toThrow(ProtocolViolation::class);
    $table->endCall('helper');
    expect(fn() => $table->requireBorrow('inv', 'writer', 'writer-type', 'helper'))->toThrow(ProtocolViolation::class);
    $table->requireOwned('inv', 'writer', 'writer-type');
    expect($table->cleanup())->toBe(['writer']);
});

it('retains tombstones after transfer or explicit drop', function (string $operation): void {
    $table = new ResourceTable('inv');
    $table->accept('inv', 'id', 'type');
    $table->{$operation}('inv', 'id', 'type');
    expect(fn() => $table->requireOwned('inv', 'id', 'type'))->toThrow(ProtocolViolation::class)
        ->and(fn() => $table->drop('inv', 'id', 'type'))->toThrow(ProtocolViolation::class)
        ->and(fn() => $table->accept('inv', 'id', 'type'))->toThrow(ProtocolViolation::class);
    expect($table->cleanup())->toBe([]);
})->with(['drop', 'transfer']);

it('rejects unknown types and cross-invocation access then invalidates everything', function (): void {
    $table = new ResourceTable('inv');
    $table->accept('inv', 'id', 'type');
    expect(fn() => $table->requireOwned('other', 'id', 'type'))->toThrow(ProtocolViolation::class)
        ->and(fn() => $table->requireOwned('inv', 'id', 'wrong-type'))->toThrow(ProtocolViolation::class)
        ->and(fn() => $table->requireOwned('inv', 'missing', 'type'))->toThrow(ProtocolViolation::class);
    expect($table->cleanup())->toBe(['id']);
    expect(fn() => $table->requireOwned('inv', 'id', 'type'))->toThrow(ProtocolViolation::class)
        ->and(fn() => $table->accept('inv', 'new', 'type'))->toThrow(ProtocolViolation::class);
    expect($table->cleanup())->toBe([]);
});
