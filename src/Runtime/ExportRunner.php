<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\CollectionExport\Exporter as LegacyCollectionExporter;
use Stashd\PluginSdk\CollectionExporter;
use Stashd\PluginSdk\Diagnostics\Trace;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\EnrichmentPlugin;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\Runtime\Codec\Envelope;
use Stashd\PluginSdk\Runtime\Codec\ExportCodec;
use Stashd\PluginSdk\Runtime\Codec\FrameChannel;
use stdClass;
use Throwable;

/**
 * Run one declared plugin component over the negotiated Stashd RPC channel.
 */
final class ExportRunner
{
    /**
     * Bind transport and diagnostics for process use or an in-memory host test.
     */
    public function __construct(
        private readonly FrameChannel $channel,
        private readonly Trace $trace,
    ) {}

    /**
     * Serve calls for the one plugin component represented by the package.
     */
    /**
     * Serve calls for the one plugin component represented by the package.
     */
    public function run(CollectionExporter|LegacyCollectionExporter|BroadcastPlugin|InputPlugin|EnrichmentPlugin $plugin, int $receiveMaximum): void
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
                $method = $frame->method;

                if (!is_string($identity) || !is_string($id) || !is_string($method) || !$params instanceof stdClass) {
                    throw new ProtocolViolation('Invalid lifecycle request fields');
                }

                if (isset($seen[$identity])) {
                    throw new ProtocolViolation('Host reused a completed invocation identity');
                }

                $seen[$identity] = true;
                $invocation = $identity;
                $this->trace->emit(TraceLevel::Basic, 'invocation.start', ['invocation' => $identity, 'id' => $id]);

                if (($plugin instanceof CollectionExporter || $plugin instanceof LegacyCollectionExporter) && $method === 'stashd:plugin/collection-export-plugin.export-collection') {
                    $result = $codec->invoke($plugin, $params);
                } elseif ($plugin instanceof InputPlugin && str_starts_with($method, 'stashd:plugin/input-plugin.')) {
                    $active = new Invocation($identity, $id, $this->channel, $this->trace, ['stashd:plugin/io-host', 'stashd:plugin/input-host', 'stashd:plugin/http-host', 'stashd:plugin/progress-host']);
                    $result = (new InputDispatcher())->invoke($plugin, $method, $params, $active);
                } elseif ($plugin instanceof BroadcastPlugin && str_starts_with($method, 'stashd:plugin/broadcast-plugin.')) {
                    $active = new Invocation($identity, $id, $this->channel, $this->trace, ['stashd:plugin/io-host', 'stashd:plugin/broadcast-host', 'stashd:plugin/http-host', 'stashd:plugin/progress-host']);
                    $result = (new BroadcastDispatcher())->invoke($plugin, $method, $params, $active);
                } elseif ($plugin instanceof EnrichmentPlugin && str_starts_with($method, 'stashd:plugin/enrichment-plugin.')) {
                    $active = new Invocation($identity, $id, $this->channel, $this->trace, ['stashd:plugin/io-host', 'stashd:plugin/enrichment-host', 'stashd:plugin/http-host', 'stashd:plugin/progress-host']);
                    $result = (new EnrichmentDispatcher())->invoke($plugin, $method, $params, $active);
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
}
