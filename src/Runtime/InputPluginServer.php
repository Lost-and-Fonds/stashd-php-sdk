<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\AcquisitionOptions;
use Stashd\PluginSdk\DiscoveryIntent;
use Stashd\PluginSdk\InputOption;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\MediaKind;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\Runtime\RuntimeFrameCodec;
use Stashd\PluginSdk\WireMapper;
use Throwable;

final class InputPluginServer
{
    /** @param Closure(PluginContext):InputPlugin $factory */
    public function __construct(private Closure $factory) {}

    public function run(): never
    {
        RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => 'sdk-hello', 'kind' => 'request', 'method' => 'hello', 'params' => []]);
        RuntimeFrameCodec::read(STDIN, 30.0);
        $plugin = ($this->factory)($this->context());
        while (($message = RuntimeFrameCodec::read(STDIN, 3600.0)) !== null) {
            $id = is_string($message['id'] ?? null) ? $message['id'] : '';

            try {
                $result = $this->dispatch($plugin, (string) ($message['method'] ?? ''), is_array($message['params'] ?? null) ? $message['params'] : []);
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'result' => $result]);
            } catch (Throwable $exception) {
                $message = $exception->getMessage();
                $lower = strtolower($message);
                $code = str_contains($lower, 'unsupported') ? 'unsupported' : (str_contains($lower, 'not found') ? 'not-found' : (str_contains($lower, 'rate') ? 'rate-limited' : (str_contains($lower, 'auth') ? 'authentication' : (str_contains($lower, 'unavailable') ? 'unavailable' : 'failed'))));
                $retryable = in_array($code, ['rate-limited', 'unavailable', 'failed'], true);
                RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'response', 'error' => ['code' => $code, 'message' => $message, 'retryable' => $retryable]]);
            }
        }
        exit(0);
    }

    private function dispatch(InputPlugin $plugin, string $method, array $params): array
    {
        return match ($method) {
            'input.resolve' => WireMapper::resolvedInput($plugin->resolve((string) ($params['source'] ?? ''))),
            'input.discover' => WireMapper::discoveredItems($plugin->discover((string) ($params['input_id'] ?? ''), DiscoveryIntent::from((string) ($params['intent'] ?? 'refresh')), $this->options($params['options'] ?? []))),
            'input.acquire' => WireMapper::acquisition($plugin->acquire(WireMapper::discoveredItemFromWire(is_array($params['item'] ?? null) ? $params['item'] : []), new AcquisitionOptions(MediaKind::from((string) ($params['media_kind'] ?? 'video')), $this->options($params['options'] ?? [])))),
            default => throw new \RuntimeException('unknown plugin method: ' . $method),
        };
    }

    private function context(): PluginContext
    {
        $call = function (string $method, array $params): array {
            static $next = 1;
            $id = 'sdk-' . $next++;
            RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'request', 'method' => $method, 'params' => $params]);
            while (($message = RuntimeFrameCodec::read(STDIN, 30.0)) !== null) {
                if (($message['id'] ?? null) !== $id) {
                    continue;
                }
                if (isset($message['error'])) {
                    throw new \RuntimeException((string) (($message['error']['message'] ?? null) ?: 'capability failed'));
                }

                return is_array($message['result'] ?? null) ? $message['result'] : [];
            }

            throw new \RuntimeException('host closed capability channel');
        };

        return new PluginContext(new RuntimeLogger($call), new RuntimeProgressReporter($call), new RuntimeHttpClient($call), new RuntimeStagingArea($call), new RuntimeHelperRunner($call));
    }

    /** @return list<InputOption> */
    private function options(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }
        $options = [];
        foreach ($values as $value) {
            if (! is_array($value) || ! is_string($value['key'] ?? null)) {
                continue;
            }
            $encoded = is_array($value['value'] ?? null) ? $value['value'] : [];
            $options[] = new InputOption($value['key'], OptionValue::fromWire($encoded));
        }

        return $options;
    }
}
