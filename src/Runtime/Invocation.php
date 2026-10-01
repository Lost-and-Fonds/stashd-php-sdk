<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\Envelope;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Resource\ResourceTable;
use stdClass;
use Throwable;

/**
 * One synchronous invocation, permitting imported calls while its lifecycle response remains outstanding.
 */
final class Invocation
{
    /**
     * Resource authority is unique to this invocation and invalidated on every exit path.
     */
    public readonly ResourceTable $resources;

    /**
     * Host-selected opaque correlation scope.
     */
    public readonly string $id;

    /**
     * Shared process channel, permanently invalidated after protocol failure.
     */
    private readonly FrameChannel $channel;

    /**
     * Safe runtime event sink, never carrying capability payloads.
     */
    private readonly Trace $trace;

    /**
     * Correlation tokens already used by either side within this invocation.
     * @var array<string, true>
     */
    private array $used;

    /**
     * Capability request sequence, independent of the outstanding host lifecycle ID.
     */
    private int $sequence = 0;

    /**
     * A closed invocation cannot issue callbacks or release requests.
     */
    private bool $active = true;

    /**
     * Interfaces granted by this lifecycle, not merely by its enclosing world.
     * @var list<string>
     */
    private readonly array $imports;

    /**
     * Establish the invocation before dispatching author code.
     * @param list<string> $imports
     */
    public function __construct(string $id, string $lifecycleId, FrameChannel $channel, Trace $trace, array $imports)
    {
        $this->id = $id;
        $this->channel = $channel;
        $this->trace = $trace;
        $this->imports = $imports;
        $this->used = [$lifecycleId => true];
        $this->resources = new ResourceTable($id);
    }

    /**
     * Issue one canonical imported call and require its exact response before allowing another call.
     * Payload validation and resource transfer are performed by the focused capability codec.
     */
    public function call(string $method, stdClass $params): mixed
    {
        if (!$this->active) {
            throw new ProtocolViolation('Capability call outside active invocation');
        }

        try {
            $interface = explode('.', $method, 2)[0];

            if (!in_array($interface, $this->imports, true) && $method !== 'stashd:plugin/rpc.resource-drop') {
                throw new ProtocolViolation('Capability is not granted to this lifecycle');
            }

            do {
                $correlation = 'plugin-' . ++$this->sequence;
            } while (isset($this->used[$correlation]));

            $this->used[$correlation] = true;
            $context = ['invocation' => $this->id, 'id' => $correlation, 'method' => $method];
            $this->trace->emit(TraceLevel::Verbose, 'capability.start', $context);
            $this->channel->write((object) ['protocol' => 1, 'kind' => 'request', 'id' => $correlation,
                'invocation' => $this->id, 'method' => $method, 'params' => $params]);
            $response = $this->channel->read() ?? throw new ProtocolViolation('Host closed during capability call');
            Envelope::response($response, $correlation, $this->id);
            $this->resources->endCall($correlation);
            $this->trace->emit(TraceLevel::Verbose, 'capability.end', $context);

            return $response->result;
        } catch (Throwable $error) {
            $this->channel->invalidate();
            $this->cleanup();
            $this->trace->emit(TraceLevel::Basic, 'protocol.failure', ['invocation' => $this->id,
                'method' => $method, 'error-class' => $error::class, 'invariant' => 'imported-call-failed']);

            throw $error;
        }
    }

    /**
     * Release a currently owned host resource explicitly; invalidate local authority before remote cleanup.
     */
    public function drop(string $id, string $type): void
    {
        $this->resources->drop($this->id, $id, $type);
        $result = $this->call('stashd:plugin/rpc.resource-drop', (object) ['resource' => (object) [
            '$resource' => (object) ['type' => $type, 'id' => $id],
        ]]);

        if ($result !== null) {
            $this->channel->invalidate();
            $this->cleanup();

            throw new ProtocolViolation('Resource drop must return unit');
        }

        $this->trace->emit(TraceLevel::Verbose, 'resource.drop', ['invocation' => $this->id,
            'resource-id' => $id, 'resource-type' => $type]);
    }

    /**
     * Fail a decoded capability boundary permanently, even if author code catches the exception.
     */
    public function violate(string $invariant): never
    {
        $this->channel->invalidate();
        $this->cleanup();
        $this->trace->emit(TraceLevel::Basic, 'protocol.failure', ['invocation' => $this->id, 'invariant' => $invariant]);

        throw new ProtocolViolation($invariant);
    }

    /**
     * Invalidate local proxies unconditionally; host cleanup owns physical resource disposal at invocation end.
     */
    public function cleanup(): void
    {
        if (!$this->active) {
            return;
        }

        $this->active = false;

        foreach ($this->resources->cleanup() as $resource) {
            $this->trace->emit(TraceLevel::Verbose, 'resource.cleanup', ['invocation' => $this->id, 'resource-id' => $resource]);
        }

        $this->trace->emit(TraceLevel::Basic, 'invocation.cleanup', ['invocation' => $this->id]);
    }
}
