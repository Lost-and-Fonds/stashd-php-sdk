<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\FinalizationRequest;
use Stashd\PluginSdk\HelperRunner;
use Stashd\PluginSdk\Item;
use Stashd\PluginSdk\ItemResource;
use Stashd\PluginSdk\OperationRequest;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\ProgressReporter;
use Stashd\PluginSdk\Preparation;
use Stashd\PluginSdk\Publication;
use Stashd\PluginSdk\PublishRequest;
use Stashd\PluginSdk\Setting;
use Stashd\PluginSdk\Source;
use Stashd\PluginSdk\StagingArea;
use Stashd\PluginSdk\WireMapper;
use Throwable;

final class PluginServer
{
    public function __construct(private BroadcastPlugin $broadcast) {}

    public function run(): never
    {
        RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => 'sdk-hello', 'kind' => 'request', 'method' => 'hello', 'params' => []]);
        RuntimeFrameCodec::read(STDIN, 30.0);

        while (($message = RuntimeFrameCodec::read(STDIN, 3600.0)) !== null) {
            $id = is_string($message['id'] ?? null) ? $message['id'] : '';

            try {
                $result = $this->dispatch(is_string($message['method'] ?? null) ? $message['method'] : '', is_array($message['params'] ?? null) ? $message['params'] : []);
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'result' => $result]);
            } catch (Throwable $exception) {
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => ['code' => 'plugin-failure', 'message' => $exception->getMessage(), 'retryable' => false]]);
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
            'broadcast.prepare' => WireMapper::preparation($this->broadcast->prepare($this->publishRequest(RuntimeFrameCodec::object($params), $context->staging, $context->helpers, $context->progress))),
            'broadcast.publish' => WireMapper::publication($this->broadcast->publish($this->publishRequest(RuntimeFrameCodec::object($params), $context->staging, $context->helpers, $context->progress))),
            'broadcast.finalize' => WireMapper::publication($this->broadcast->finalize(new FinalizationRequest($this->publishRequest(RuntimeFrameCodec::object($params['request'] ?? []), $context->staging, $context->helpers, $context->progress), $this->publication(RuntimeFrameCodec::object($params['publication'] ?? []))), $context)),
            'broadcast.operation' => WireMapper::operationResult($this->broadcast->operation($this->operationRequest(RuntimeFrameCodec::object($params)), $context)),
            default => throw new \RuntimeException('unknown plugin method: ' . $method),
        };
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

        return new PluginContext(new RuntimeLogger($call), new RuntimeProgressReporter($call), new RuntimeHttpClient($call), new RuntimeStagingArea($call), $helpers);
    }

    /** @param array<string, mixed> $data */
    private function publishRequest(array $data, ?StagingArea $staging = null, ?HelperRunner $helpers = null, ?ProgressReporter $progress = null): PublishRequest
    {
        return WireMapper::publishRequestFromWire($data, $staging, $helpers, $progress);
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
}
