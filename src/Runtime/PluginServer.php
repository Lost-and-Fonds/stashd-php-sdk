<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\FinalizationRequest;
use Stashd\PluginSdk\OperationRequest;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\PluginRegistry;
use Stashd\PluginSdk\StashCollection;
use Stashd\PluginSdk\StashCollectionEntry;
use Stashd\PluginSdk\Publication;
use Stashd\PluginSdk\PublishRequest;
use Stashd\PluginSdk\WireMapper;
use Stashd\PluginSdk\CapabilityUnavailableException;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\PluginFailureException;
use Throwable;

final class PluginServer
{
    private PluginRegistry $registry;

    public function __construct(PluginRegistry|BroadcastPlugin $plugins)
    {
        $this->registry = $plugins instanceof PluginRegistry ? $plugins : new PluginRegistry();

        if ($plugins instanceof BroadcastPlugin) {
            $this->registry->broadcast('default', $plugins);
        }
    }

    public function run(): never
    {
        RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => 'sdk-hello', 'kind' => 'request', 'method' => 'hello', 'params' => ['min' => 1, 'max' => 1]]);
        $this->handshake();

        while (($message = RuntimeFrameCodec::read(STDIN, 3600.0)) !== null) {
            $id = is_string($message['id'] ?? null) ? $message['id'] : '';

            try {
                $result = $this->dispatch(is_string($message['method'] ?? null) ? $message['method'] : '', is_array($message['params'] ?? null) ? $message['params'] : []);
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'result' => $result]);
            } catch (PluginFailureException $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => WireMapper::pluginFailure($exception->failure)]);
            } catch (CapabilityUnavailableException $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => WireMapper::pluginFailure(new PluginFailure(PluginErrorCode::Unavailable, new PluginError($exception->getMessage(), true)))]);
            } catch (Throwable $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => WireMapper::pluginFailure(new PluginFailure(PluginErrorCode::Failed, new PluginError($exception->getMessage(), false)))]);
            }
        }
        exit(0);
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<int|string, mixed>
     */
    private function dispatch(string $method, array $params): array
    {
        $context = $this->context();

        return match ($method) {
            'broadcast.prepare' => WireMapper::preparation($this->registry->broadcastPlugin('default')->prepare($this->publishRequest(RuntimeFrameCodec::object($params)), $context)),
            'broadcast.publish' => WireMapper::publication($this->registry->broadcastPlugin('default')->publish($this->publishRequest(RuntimeFrameCodec::object($params)), $context)),
            'broadcast.finalize' => WireMapper::publication($this->registry->broadcastPlugin('default')->finalize(new FinalizationRequest($this->publishRequest(RuntimeFrameCodec::object($params['request'] ?? [])), $this->publication(RuntimeFrameCodec::object($params['publication'] ?? []))), $context)),
            'broadcast.operation' => WireMapper::operationResult($this->registry->broadcastPlugin('default')->operation($this->operationRequest(RuntimeFrameCodec::object($params)), $context)),
            'stash.collection.export' => $this->export(RuntimeFrameCodec::object($params), $context),
            default => throw new \RuntimeException('unknown plugin method: ' . $method),
        };
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, string>
     */
    private function export(array $params, PluginContext $context): array
    {
        $exporter = $this->registry->collectionExporterFor($this->requiredString($params, 'exporter'));
        $entries = [];

        foreach (is_array($params['entries'] ?? null) ? $params['entries'] : [] as $entry) {
            $entry = RuntimeFrameCodec::object($entry);
            $entries[] = new StashCollectionEntry(
                $this->requiredString($entry, 'stash-name'),
                $this->requiredString($entry, 'broadcast-key'),
                $this->requiredString($entry, 'broadcast-name'),
                $this->requiredString($entry, 'public-url'),
            );
        }

        $file = $exporter->export(new StashCollection($entries), $context);

        return ['filename' => $file->filename, 'content-type' => $file->contentType, 'contents' => $file->contents];
    }

    /** @param array<string, mixed> $values */
    private function requiredString(array $values, string $key): string
    {
        $value = $values[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new \RuntimeException("Missing string value: {$key}");
        }

        return $value;
    }

    private function context(): PluginContext
    {
        $call = function (string $method, array $params): array {
            static $next = 1;
            /** @var int $next */
            $id = 'sdk-' . $next++;
            RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'request', 'method' => $method, 'params' => $params]);

            while (($message = RuntimeFrameCodec::read(STDIN, 300.0)) !== null) {
                if (($message['id'] ?? null) !== $id) {
                    continue;
                }

                if (is_array($message['error'] ?? null)) {
                    throw new \RuntimeException(is_string($message['error']['message'] ?? null) ? $message['error']['message'] : 'capability failed');
                }

                return is_array($message['result'] ?? null) ? $message['result'] : [];
            }

            throw new \RuntimeException('host closed capability channel');
        };

        $helpers = new RuntimeHelperRunner($call);

        return new PluginContext(new RuntimeLogger($call), new RuntimeProgressReporter($call), new RuntimeHttpClient($call), new RuntimeStagingArea($call), $helpers, '/plugin-data', '/staging');
    }

    /** @param array<string, mixed> $data */
    private function publishRequest(array $data): PublishRequest
    {
        return WireMapper::publishRequestFromWire($data);
    }

    /** @param array<string,mixed> $data */
    private function operationRequest(array $data): OperationRequest
    {
        return WireMapper::operationRequestFromWire($data);
    }

    /** @param array<string,mixed> $data */
    private function publication(array $data): Publication
    {
        return WireMapper::publicationFromWire($data);
    }

    private function handshake(): void
    {
        $response = RuntimeFrameCodec::read(STDIN, 30.0);

        if ($response === null || ($response['id'] ?? null) !== 'sdk-hello' || ($response['kind'] ?? null) !== 'response') {
            throw new \RuntimeException('plugin RPC handshake failed');
        }
        $result = RuntimeFrameCodec::object($response['result'] ?? null);

        if (($result['protocol'] ?? null) !== 1 || ($result['min'] ?? null) !== 1 || ($result['max'] ?? null) !== 1) {
            throw new \RuntimeException('unsupported plugin RPC protocol');
        }
    }
}
