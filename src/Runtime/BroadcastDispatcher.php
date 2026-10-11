<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Broadcast\Action;
use Stashd\PluginSdk\Broadcast\Failure;
use Stashd\PluginSdk\Broadcast\Publish;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\Contract\BroadcastPlugin\BroadcastPluginMethod;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Runtime\Codec\BroadcastAuthorCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastPluginCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\Resource\RemoteHostResource;
use Stashd\PluginSdk\Tools;
use stdClass;

/**
 * Dispatch publication and interactive actions for a Broadcast component.
 */
final class BroadcastDispatcher
{
    /**
     * Decode the requested lifecycle and return its host result.
     */
    public function invoke(BroadcastPlugin $plugin, BroadcastPluginMethod $method, stdClass $params, Invocation $invocation): stdClass
    {
        try {
            return match ($method) {
                BroadcastPluginMethod::Operation => $this->action($plugin, $params, $invocation),
                BroadcastPluginMethod::Publish => $this->publish($plugin, $params, $invocation),
            };
        } catch (Failure $failure) {
            return (object) ['error' => (object) ['tag' => 'failed', 'value' => (object) ['message' => $failure->getMessage(), 'retryable' => $failure->retryable]]];
        }
    }

    /**
     * Run a named destination action without treating it as publication.
     */
    private function action(BroadcastPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['request', 'credentials']);
        $request = BroadcastPluginCodec::decodeOperationRequest($record->request);
        $helpers = $this->helpers($invocation, $record->credentials);
        $action = new Action($request->name, BroadcastAuthorCodec::settings($request->settings), BroadcastAuthorCodec::settings($request->payload), $helpers);

        return BroadcastAuthorCodec::actionResult($plugin->action($action));
    }

    /**
     * Publish the fixed selected collection with its reporter and settings.
     */
    private function publish(BroadcastPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['request', 'configuration', 'credentials']);
        $request = Values::record($record->request, ['collection', 'reporter', 'maximum-report-records-per-batch']);
        $configuration = BroadcastPluginCodec::decodeDestinationConfiguration($record->configuration);
        $collection = ResourceValueCodec::decode($request->collection, ['kind' => 'named', 'name' => 'item-collection'], 'broadcast-plugin', $invocation);
        $reporter = ResourceValueCodec::decode($request->reporter, ['kind' => 'named', 'name' => 'publication-reporter'], 'broadcast-plugin', $invocation);

        if (!$collection instanceof RemoteHostResource || !$reporter instanceof RemoteHostResource) {
            $invocation->violate('Publication requires a selected collection and reporter');
        }

        $publish = new Publish($invocation, $collection, $reporter, Values::integer('u32', $request->{'maximum-report-records-per-batch'}), BroadcastAuthorCodec::settings($configuration->settings), $this->helpers($invocation, $record->credentials));

        $publication = $plugin->publish($publish);
        $publish->requireComplete();

        return BroadcastAuthorCodec::publication($publication);
    }

    /**
     * Convert credential references into selectors rather than exposing secrets.
     */
    private function helpers(Invocation $invocation, mixed $wire): Tools
    {
        $credentials = [];

        foreach (Values::list($wire) as $entry) {
            $binding = IoHostCodec::decodeCredentialBinding($entry);
            $credentials[] = new Credential($binding->name, $binding->reference->id);
        }

        return new Tools($invocation, $credentials);
    }
}
