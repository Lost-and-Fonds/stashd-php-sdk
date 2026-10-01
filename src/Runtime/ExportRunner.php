<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\CollectionExport\Exporter;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\Envelope;
use Stashd\PluginSdk\Runtime\Codec\ExportCodec;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
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
    public function run(Exporter $exporter, int $receiveMaximum): void
    {
        $invocation = null;

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

                if ($frame->method !== 'stashd:plugin/collection-export-plugin.export-collection') {
                    throw new ProtocolViolation('Unsupported lifecycle method for Collection Export component');
                }

                $result = $codec->invoke($exporter, $params);
                $this->channel->write((object) ['protocol' => 1, 'id' => $id, 'kind' => 'response',
                    'invocation' => $identity, 'result' => $result]);
                $this->trace->emit(TraceLevel::Basic, 'invocation.end', ['invocation' => $identity, 'id' => $id]);
                $invocation = null;
            }
        } catch (Throwable $error) {
            $this->channel->invalidate();
            $this->trace->emit(TraceLevel::Basic, 'invocation.failed', ['invocation' => $invocation,
                'error-class' => $error::class, 'invariant' => 'channel-unusable']);

            throw $error;
        } finally {
            $this->trace->emit(TraceLevel::Basic, 'cleanup', ['invocation' => $invocation]);
        }
    }
}
