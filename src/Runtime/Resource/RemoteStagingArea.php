<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Invocation-bound staging area for creating helper output writers.
 */
final class RemoteStagingArea implements OwnedResource
{
    /**
     * Active invocation that owns this staging area.
     */
    private readonly Invocation $invocation;

    /**
     * Opaque identity of the host-created staging area.
     */
    private readonly string $id;

    /**
     * Bind a host-created staging area to its active invocation.
     */
    public function __construct(Invocation $invocation, string $id)
    {
        $this->invocation = $invocation;
        $this->id = $id;
        $invocation->resources->requireOwned($invocation->id, $id, 'stashd:plugin/io-host.staging-area');
    }

    /**
     * Verify the original invocation still owns this area.
     */
    public function resourceId(ResourceTable $table, string $invocation, string $type): string
    {
        if ($table !== $this->invocation->resources || $invocation !== $this->invocation->id || $type !== 'stashd:plugin/io-host.staging-area') {
            throw new ProtocolViolation('Staging area belongs to another invocation or type');
        }

        $table->requireOwned($invocation, $this->id, $type);

        return $this->id;
    }

    /**
     * Create a new unfinished writer with the requested media type.
     */
    public function create(?string $mediaType): RemoteStagedWriter
    {
        $result = $this->invocation->typedCall('stashd:plugin/io-host.staging-area.create', [
            'self' => $this, 'media-type' => $mediaType, 'metadata' => [],
        ], [
            'self' => ['kind' => 'borrow', 'value' => ['kind' => 'named', 'name' => 'staging-area']],
            'media-type' => ['kind' => 'option', 'value' => ['kind' => 'scalar', 'name' => 'string']],
            'metadata' => ['kind' => 'list', 'value' => ['kind' => 'named', 'name' => 'plugin-metadata']],
        ], ['kind' => 'result', 'ok' => ['kind' => 'named', 'name' => 'staged-writer'], 'error' => ['kind' => 'named', 'name' => 'staging-error']], 'io-host');

        if (!$result instanceof stdClass) {
            $this->invocation->violate('Staging create requires a WIT result');
        }

        if (property_exists($result, 'error')) {
            throw new HostFailure(IoHostCodec::decodeStagingError($result->error));
        }

        if (!$result->ok instanceof RemoteStagedWriter) {
            $this->invocation->violate('Staging create did not return a writer');
        }

        return $result->ok;
    }

    /**
     * Release the staging area without affecting finished artifacts.
     */
    public function close(): void
    {
        $this->invocation->drop($this->id, 'stashd:plugin/io-host.staging-area');
    }
}
