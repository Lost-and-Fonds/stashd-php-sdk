<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\HostFailure;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Exact io-host.start-helper call boundary, keeping resource ownership behind the runtime.
 */
final class HelperProcessCall
{
    /**
     * Start a host-approved process, consuming optional input and staged output when the frame is accepted.
     * @param list<string> $args
     * @param list<CredentialBinding> $credentials
     */
    public static function start(Invocation $invocation, string $name, array $args, ?OwnedResource $input, ?RemoteStagedWriter $output, array $credentials): RemoteHelperProcess
    {
        $invocation->trace(TraceLevel::Ludicrous, 'helper.start', ['method' => 'stashd:plugin/io-host.start-helper']);
        $transfers = [];
        $inputType = ['kind' => 'option', 'value' => ['kind' => 'named', 'name' => 'byte-stream']];
        $outputType = ['kind' => 'option', 'value' => ['kind' => 'named', 'name' => 'staged-writer']];
        $params = (object) [
            'name' => Values::text($name),
            'args' => array_map(static fn(mixed $arg): string => Values::text($arg), Values::list($args)),
            'input' => ResourceValueCodec::encode($input, $inputType, 'io-host', $invocation, $transfers),
            'output' => ResourceValueCodec::encode($output, $outputType, 'io-host', $invocation, $transfers),
            'credentials' => array_map(static function (mixed $binding): stdClass {
                if (!$binding instanceof CredentialBinding) {
                    throw new ProtocolViolation('Helper credential binding has wrong type');
                }

                return IoHostCodec::encodeCredentialBinding($binding);
            }, Values::list($credentials)),
        ];
        $writerId = $output?->resourceId($invocation->resources, $invocation->id, 'stashd:plugin/io-host.staged-writer');
        $result = $invocation->call('stashd:plugin/io-host.start-helper', $params, $transfers);

        try {
            if (!$result instanceof stdClass) {
                throw new ProtocolViolation('Helper start requires a WIT result');
            }

            if (property_exists($result, 'error')) {
                Values::record($result, ['error']);

                throw new HostFailure(IoHostCodec::decodeHelperError($result->error));
            }

            Values::record($result, ['ok']);
            $process = ResourceValueCodec::decode($result->ok, ['kind' => 'named', 'name' => 'helper-process'], 'io-host', $invocation, $writerId);

            if (!$process instanceof RemoteHelperProcess) {
                throw new ProtocolViolation('Host did not return a helper process');
            }

            return $process;
        } catch (ProtocolViolation $error) {
            $invocation->violate($error->getMessage());
        }
    }
}
