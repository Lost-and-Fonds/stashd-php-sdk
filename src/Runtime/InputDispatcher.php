<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Input\Acquisition;
use Stashd\PluginSdk\Input\Discovery;
use Stashd\PluginSdk\Input\Resolve;
use Stashd\PluginSdk\Input\ResolveDelegation;
use Stashd\PluginSdk\InputPlugin;
use Stashd\PluginSdk\Runtime\Codec\Generated\InputHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\InputPluginCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\InputAuthorCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;

/**
 * Dispatch the Input world's public methods through one active plugin call.
 */
final class InputDispatcher
{
    /**
     * Decode a method and invoke the corresponding author API.
     */
    public function invoke(InputPlugin $plugin, string $method, stdClass $params, Invocation $invocation): stdClass
    {
        return match ($method) {
            'stashd:plugin/input-plugin.resolve' => $this->resolve($plugin, $params, $invocation),
            'stashd:plugin/input-plugin.resolve-delegation' => $this->delegation($plugin, $params, $invocation),
            'stashd:plugin/input-plugin.discover' => $this->discover($plugin, $params, $invocation),
            'stashd:plugin/input-plugin.acquire' => $this->acquire($plugin, $params, $invocation),
            default => throw new ProtocolViolation('Unsupported Input lifecycle method'),
        };
    }

    /**
     * Decode host credential selectors without exposing secret values.
     */
    private function helpers(Invocation $invocation, mixed $wire): Helpers
    {
        $credentials = [];

        foreach (Values::list($wire) as $entry) {
            $binding = IoHostCodec::decodeCredentialBinding($entry);
            $credentials[] = new Credential($binding->name, $binding->reference->id);
        }

        return new Helpers($invocation, $credentials);
    }

    /**
     * Identify a caller-provided source.
     */
    private function resolve(InputPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['source', 'credentials']);

        return (object) ['ok' => InputAuthorCodec::resolved($plugin->resolve(new Resolve(InputAuthorCodec::source($record->source), $this->helpers($invocation, $record->credentials))))];
    }

    /**
     * Identify a reference delegated by another Input.
     */
    private function delegation(InputPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['delegation', 'credentials']);
        $delegation = InputHostCodec::decodeInputDelegation($record->delegation);

        return (object) ['ok' => InputAuthorCodec::resolved($plugin->resolveDelegation(new ResolveDelegation($delegation->reference, $this->helpers($invocation, $record->credentials))))];
    }

    /**
     * Commit bounded discovery batches before returning lifecycle success.
     */
    private function discover(InputPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['request', 'credentials']);
        $request = InputPluginCodec::decodeDiscoveryRequest($record->request);
        $discovery = new Discovery($invocation, $request->inputId, $request->intent->value, InputAuthorCodec::options($request->options), $request->continuation?->value, $request->refreshState?->value, $request->maximumItemsPerBatch, $this->helpers($invocation, $record->credentials));
        $plugin->discover($discovery);

        if (!$discovery->finished()) {
            $invocation->violate('Discovery returned without an acknowledged terminal batch');
        }

        return (object) ['ok' => null];
    }

    /**
     * Save a discovered item with its selected options and credential bindings.
     */
    private function acquire(InputPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['item', 'options']);
        $options = InputPluginCodec::decodeAcquisitionOptions($record->options);
        $result = $plugin->acquire(new Acquisition(InputAuthorCodec::item($record->item), InputAuthorCodec::options($options->options), $this->helpers($invocation, array_map(IoHostCodec::encodeCredentialBinding(...), $options->credentials))));

        return (object) ['ok' => InputAuthorCodec::acquired($result)];
    }
}
