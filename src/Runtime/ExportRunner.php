<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Broadcast\Choice;
use Stashd\PluginSdk\Broadcast\Operation;
use Stashd\PluginSdk\Broadcast\OperationResult;
use Stashd\PluginSdk\Broadcast\Setting;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueBoolean;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueNumber;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueText;
use Stashd\PluginSdk\Contract\BroadcastPlugin\Setting as ContractSetting;
use Stashd\PluginSdk\CollectionExport\Exporter;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\Envelope;
use Stashd\PluginSdk\Runtime\Codec\ExportCodec;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastPluginCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;
use Throwable;

/**
 * Sequential Collection Export process runtime with plugin-first hello and fail-closed dispatch.
 */
final class ExportRunner
{
    /**
     * Frame channel exclusively bound to protocol streams.
     */
    private readonly FrameChannel $channel;

    /**
     * Safe structured diagnostics, separate from protocol output.
     */
    private readonly Trace $trace;

    /**
     * Bind transport and diagnostics explicitly for both process use and host-simulator tests.
     */
    public function __construct(FrameChannel $channel, Trace $trace)
    {
        $this->channel = $channel;
        $this->trace = $trace;
    }

    /**
     * Run sequential invocations until clean EOF; any contract failure permanently invalidates the channel.
     */
    public function run(Exporter|BroadcastPlugin $exporter, int $receiveMaximum): void
    {
        $invocation = null;
        $active = null;

        try {
            if ($receiveMaximum < 4096 || $receiveMaximum > 4294967295) {
                throw new ProtocolViolation('Invalid local receive maximum');
            }

            $this->trace->emit(TraceLevel::Basic, 'startup');
            $helloId = 'hello';
            $this->channel->write((object) ['protocol' => 1, 'id' => $helloId, 'kind' => 'request',
                'method' => 'hello', 'params' => (object) ['min' => 1, 'max' => 1, 'max-frame-bytes' => $receiveMaximum]]);
            $hello = $this->channel->read() ?? throw new ProtocolViolation('Host closed before hello response');
            $peerMaximum = Envelope::hello($hello, $helloId);
            $this->channel->negotiate($receiveMaximum, $peerMaximum);
            $this->trace->emit(TraceLevel::Basic, 'hello.accepted', ['receive-maximum' => $receiveMaximum, 'send-maximum' => $peerMaximum]);
            $seen = [];
            $codec = new ExportCodec();

            while (($frame = $this->channel->read()) !== null) {
                Envelope::request($frame);
                $identity = $frame->invocation;
                $id = $frame->id;
                $params = $frame->params;

                if (!is_string($identity) || !is_string($id) || !$params instanceof stdClass) {
                    throw new ProtocolViolation('Invalid lifecycle request fields');
                }

                if (isset($seen[$identity])) {
                    throw new ProtocolViolation('Host reused a completed invocation identity');
                }

                $seen[$identity] = true;
                $invocation = $identity;
                $this->trace->emit(TraceLevel::Basic, 'invocation.start', ['invocation' => $identity, 'id' => $id]);

                if ($exporter instanceof Exporter && $frame->method === 'stashd:plugin/collection-export-plugin.export-collection') {
                    $result = $codec->invoke($exporter, $params);
                } elseif ($exporter instanceof BroadcastPlugin && $frame->method === 'stashd:plugin/broadcast-plugin.operation') {
                    Values::record($params, ['request', 'credentials']);
                    $request = BroadcastPluginCodec::decodeOperationRequest($params->request);
                    $credentials = [];

                    foreach (Values::list($params->credentials) as $binding) {
                        $binding = IoHostCodec::decodeCredentialBinding($binding);
                        $credentials[] = new Credential($binding->name, $binding->reference->id);
                    }

                    $active = new Invocation($identity, $id, $this->channel, $this->trace, ['stashd:plugin/io-host']);
                    $response = $exporter->operation(new Operation($request->name, self::settings($request->settings), self::settings($request->payload)), new Helpers($active, $credentials));
                    $result = self::operationResult($response);
                } else {
                    throw new ProtocolViolation('Unsupported lifecycle method for component');
                }

                $this->channel->write((object) ['protocol' => 1, 'id' => $id, 'kind' => 'response',
                    'invocation' => $identity, 'result' => $result]);
                $active?->cleanup();
                $active = null;
                $this->trace->emit(TraceLevel::Basic, 'invocation.end', ['invocation' => $identity, 'id' => $id]);
                $invocation = null;
            }
        } catch (Throwable $error) {
            $this->channel->invalidate();
            $this->trace->emit(TraceLevel::Basic, 'invocation.failed', ['invocation' => $invocation,
                'error-class' => $error::class, 'invariant' => 'channel-unusable']);

            throw $error;
        } finally {
            $active?->cleanup();
            $this->trace->emit(TraceLevel::Basic, 'cleanup', ['invocation' => $invocation]);
        }
    }

    /**
     * Map ordered Broadcast settings to public typed values without changing their keys.
     * @param list<ContractSetting> $values
     * @return list<Setting>
     */
    private static function settings(array $values): array
    {
        $mapped = [];

        foreach ($values as $setting) {
            $mapped[] = new Setting($setting->key, match (true) {
                $setting->value instanceof OptionValueBoolean, $setting->value instanceof OptionValueNumber, $setting->value instanceof OptionValueText => $setting->value->value,
                default => throw new ProtocolViolation('Unknown Broadcast setting value'),
            });
        }

        return $mapped;
    }

    /**
     * Encode the plugin's actual choices and setting updates as the declared operation result.
     */
    private static function operationResult(OperationResult $response): stdClass
    {
        $values = array_map(static function (Setting $setting): ContractSetting {
            $value = match (true) {
                is_bool($setting->value) => new OptionValueBoolean($setting->value),
                is_int($setting->value) => new OptionValueNumber($setting->value),
                default => new OptionValueText($setting->value),
            };

            return new ContractSetting($setting->key, $value);
        }, $response->values);

        return (object) ['ok' => (object) [
            'choices' => array_map(static fn(Choice $choice): stdClass => (object) ['value' => $choice->value, 'label' => $choice->label], $response->choices),
            'values' => array_map(BroadcastPluginCodec::encodeSetting(...), $values),
        ]];
    }
}
