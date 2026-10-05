<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Owned collection or reporter supplied by a host for one plugin call.
 */
final class RemoteHostResource implements OwnedResource
{
    /**
     * Create a resource wrapper for its owning call.
     *
     * @param Invocation $invocation Active call that owns the resource.
     * @param string $id Host-issued resource ID.
     * @param string $type Declared resource type.
     */
    public function __construct(
        private readonly Invocation $invocation,
        private readonly string $id,
        private readonly string $type,
    ) {
        $invocation->resources->requireOwned($invocation->id, $id, $type);
    }

    /**
     * Return its ID only while this call owns the requested resource type.
     */
    public function resourceId(ResourceTable $table, string $invocation, string $type): string
    {
        if ($table !== $this->invocation->resources || $invocation !== $this->invocation->id || $type !== $this->type) {
            throw new ProtocolViolation('Host resource belongs to another call or type');
        }

        $table->requireOwned($invocation, $this->id, $type);

        return $this->id;
    }
}
