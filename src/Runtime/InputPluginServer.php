<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\AcquisitionOptions;
use Stashd\PluginSdk\ArtifactRole;
use Stashd\PluginSdk\DiscoveryIntent;
use Stashd\PluginSdk\InputOption;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\InvalidPluginResultException;
use Stashd\PluginSdk\MediaKind;
use Stashd\PluginSdk\OptionValue;
use Stashd\PluginSdk\PluginContext;
use Stashd\PluginSdk\Runtime\RuntimeFrameCodec;
use Stashd\PluginSdk\WireMapper;
use Stashd\PluginSdk\CapabilityUnavailableException;
use Stashd\PluginSdk\PluginError;
use Stashd\PluginSdk\PluginErrorCode;
use Stashd\PluginSdk\PluginFailure;
use Stashd\PluginSdk\PluginFailureException;
use Throwable;

final class InputPluginServer
{
    /** @param Closure(PluginContext):InputPlugin $factory */
    public function __construct(private Closure $factory) {}

    public function run(): never
    {
        RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => 'sdk-hello', 'kind' => 'request', 'method' => 'hello', 'params' => ['min' => 1, 'max' => 1]]);
        $this->handshake();
        $plugin = ($this->factory)($this->context());

        while (($message = RuntimeFrameCodec::read(STDIN, 3600.0)) !== null) {
            $id = is_string($message['id'] ?? null) ? $message['id'] : '';

            try {
                $result = $this->dispatch($plugin, is_string($message['method'] ?? null) ? $message['method'] : '', is_array($message['params'] ?? null) ? $message['params'] : []);
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
    private function dispatch(InputPlugin $plugin, string $method, array $params): array
    {
        return match ($method) {
            'input.resolve' => WireMapper::resolvedInput($plugin->resolve(WireMapper::sourceDescriptorFromWire($params['source'] ?? []))),
            'input.discover' => WireMapper::discoveredItems($plugin->discover(is_string($params['input_id'] ?? null) ? $params['input_id'] : '', DiscoveryIntent::from(is_string($params['intent'] ?? null) ? $params['intent'] : 'refresh'), $this->options($params['options'] ?? []))),
            'input.acquire' => WireMapper::acquisition($plugin->acquire(WireMapper::discoveredItemFromWire(RuntimeFrameCodec::object($params['item'] ?? [])), new AcquisitionOptions(MediaKind::from(is_string($params['media_kind'] ?? null) ? $params['media_kind'] : 'video'), $this->options($params['options'] ?? []), $this->artifactRoles($params['requested_roles'] ?? null)))),
            default => throw new \RuntimeException('unknown plugin method: ' . $method),
        };
    }

    /** @return list<ArtifactRole>|null */
    private function artifactRoles(mixed $roles): ?array
    {
        if ($roles === null) {
            return null;
        }

        if (! is_array($roles)) {
            throw new InvalidPluginResultException('requested_roles must be a list');
        }

        /** @var list<mixed> $roles */
        $roles = array_values($roles);

        return array_map(static fn(mixed $role): ArtifactRole => ArtifactRole::from(is_string($role) ? $role : ''), $roles);
    }

    private function context(): PluginContext
    {
        $call = function (string $method, array $params, ?callable $onOutput = null): array {
            static $next = 1;
            /** @var int $next */
            $id = 'sdk-' . $next++;
            RuntimeFrameCodec::write(STDOUT, ['protocol' => 1, 'id' => $id, 'kind' => 'request', 'method' => $method, 'params' => $params]);

            while (($message = RuntimeFrameCodec::read(STDIN, 300.0)) !== null) {
                if (($message['kind'] ?? null) === 'notification') {
                    if (($message['method'] ?? null) === 'helper.output' && $onOutput !== null) {
                        $params = is_array($message['params'] ?? null) ? $message['params'] : [];
                        $onOutput(is_string($params['channel'] ?? null) ? $params['channel'] : 'out', is_string($params['buffer'] ?? null) ? $params['buffer'] : '');
                    }

                    continue;
                }

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

        return new PluginContext(new RuntimeLogger($call), new RuntimeProgressReporter($call), new RuntimeHttpClient($call), new RuntimeStagingArea($call), new RuntimeHelperRunner($call), '/plugin-data');
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

    /**
     * @param mixed $values
     * @return list<InputOption>
     */
    private function options(mixed $values): array
    {
        if (! is_array($values)) {
            throw new InvalidPluginResultException('input options must be a list');
        }
        $options = [];

        foreach ($values as $value) {
            if (! is_array($value) || ! is_string($value['key'] ?? null) || ! is_array($value['value'] ?? null)) {
                throw new InvalidPluginResultException('input option is malformed');
            }
            $options[] = new InputOption($value['key'], OptionValue::fromWire($value['value']));
        }

        return $options;
    }
}
