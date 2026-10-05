<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\Envelope;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
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
     * Establish the invocation before dispatching author code.
     * @param list<string> $imports
     */
    public function __construct(
        public readonly string $id,
        string $lifecycleId,
        private readonly FrameChannel $channel,
        private readonly Trace $trace,
        private readonly array $imports,
    ) {
        $this->used = [$lifecycleId => true];
        $this->resources = new ResourceTable($id);
    }

    /**
     * Issue one canonical imported call and require its exact response before allowing another call.
     * Payload validation and resource transfer are performed by the focused capability codec.
     * @param list<array{string, string}> $transfers
     */
    public function call(string $method, stdClass $params, array $transfers = []): mixed
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

            $this->resources->validateTransfers($this->id, $transfers);
            $this->used[$correlation] = true;
            $context = ['invocation' => $this->id, 'id' => $correlation, 'method' => $method];
            $this->trace->emit(TraceLevel::Verbose, 'capability.start', $context);
            $this->channel->write((object) ['protocol' => 1, 'kind' => 'request', 'id' => $correlation,
                'invocation' => $this->id, 'method' => $method, 'params' => $params]);

            foreach ($transfers as [$id, $type]) {
                $this->resources->transfer($this->id, $id, $type);
                $this->trace->emit(TraceLevel::Ludicrous, 'resource.transfer', ['invocation' => $this->id,
                    'resource-id' => $id, 'resource-type' => $type]);
            }

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
     * Send a WIT call with nested owned resources, committing transfers only after a complete request frame.
     * @param array<string, array<string, mixed>> $arguments
     * @param array<string, mixed> $resultType
     * @param array<string, mixed> $values
     */
    public function typedCall(string $method, array $values, array $arguments, array $resultType, string $interface, ?string $writerId = null): mixed
    {
        $transfers = [];
        $params = new stdClass();

        foreach ($arguments as $name => $schema) {
            if (!array_key_exists($name, $values)) {
                $this->violate('Missing WIT argument');
            }

            $params->{$name} = ResourceValueCodec::encode($values[$name], $schema, $interface, $this, $transfers);
        }

        foreach ($values as $name => $_) {
            if (!isset($arguments[$name])) {
                $this->violate('Unexpected WIT argument');
            }
        }

        return $this->callWithTransfer($method, $params, $transfers, $resultType, $interface, $writerId);
    }

    /**
     * Validate the encoded frame before consumption and decode the correlated typed result.
     * @param list<array{string, string}> $transfers
     * @param array<string, mixed> $resultType
     */
    private function callWithTransfer(string $method, stdClass $params, array $transfers, array $resultType, string $interface, ?string $writerId): mixed
    {
        $result = $this->call($method, $params, $transfers);

        try {
            return ResourceValueCodec::decode($result, $resultType, $interface, $this, $writerId);
        } catch (ProtocolViolation $error) {
            $this->violate($error->getMessage());
        }
    }

    /**
     * Reject use of a completed plugin call before returning saved resources.
     */
    public function requireActive(): void
    {
        if (!$this->active) {
            throw new ProtocolViolation('Resource belongs to a completed plugin call');
        }
    }

    /**
     * Expose safe metadata about process events without serializing helper output or credentials.
     * @param array<string, scalar|null> $context
     */
    public function trace(TraceLevel $level, string $event, array $context = []): void
    {
        $this->trace->emit($level, $event, ['invocation' => $this->id] + $context);
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
