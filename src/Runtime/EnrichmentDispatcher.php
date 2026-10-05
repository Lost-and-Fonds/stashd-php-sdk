<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Stashd\PluginSdk\Contract\EnrichmentHost\ItemContext;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationValue as ContractSelection;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorInvalidConfiguration;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Enrichment\Item;
use Stashd\PluginSdk\Enrichment\Request;
use Stashd\PluginSdk\Enrichment\Selection;
use Stashd\PluginSdk\EnrichmentPlugin;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helpers;
use Stashd\PluginSdk\Runtime\Codec\AuthorValues;
use Stashd\PluginSdk\Runtime\Codec\Generated\EnrichmentHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\EnrichmentPluginCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;

/**
 * Dispatch capability discovery and enrichment for one item.
 */
final class EnrichmentDispatcher
{
    /**
     * Dispatch exactly one declared Enrichment method.
     */
    public function invoke(EnrichmentPlugin $plugin, string $method, stdClass $params, Invocation $invocation): stdClass
    {
        return match ($method) {
            'stashd:plugin/enrichment-plugin.capabilities' => $this->capabilities($plugin, $params),
            'stashd:plugin/enrichment-plugin.enrich' => $this->enrich($plugin, $params, $invocation),
            default => throw new ProtocolViolation('Unsupported Enrichment lifecycle method'),
        };
    }

    /**
     * Run deterministic capability discovery without granting credentials or host calls.
     */
    private function capabilities(EnrichmentPlugin $plugin, stdClass $params): stdClass
    {
        $record = Values::record($params, ['context']);
        $context = EnrichmentHostCodec::decodeItemContext($record->context);
        $publicContext = self::item($context);
        $capabilities = array_map(EnrichmentPluginCodec::decodeCapability(...), $plugin->capabilities($publicContext));
        EnrichmentValidation::descriptors($capabilities);

        return (object) ['ok' => array_map(EnrichmentPluginCodec::encodeCapability(...), $capabilities)];
    }

    /**
     * Execute one advertised capability with validated caller selections.
     */
    private function enrich(EnrichmentPlugin $plugin, stdClass $params, Invocation $invocation): stdClass
    {
        $record = Values::record($params, ['context', 'capability-id', 'capability-revision', 'configuration', 'credentials']);
        $context = EnrichmentHostCodec::decodeItemContext($record->context);
        $publicContext = self::item($context);
        $capabilities = array_map(EnrichmentPluginCodec::decodeCapability(...), $plugin->capabilities($publicContext));
        $configuration = array_map(EnrichmentPluginCodec::decodeConfigurationValue(...), Values::list($record->configuration));
        $selected = EnrichmentValidation::select($capabilities, Values::text($record->{'capability-id'}), Values::text($record->{'capability-revision'}), $configuration);

        if ($selected instanceof PluginErrorUnsupported || $selected instanceof PluginErrorInvalidConfiguration) {
            return (object) ['error' => EnrichmentPluginCodec::encodePluginError($selected)];
        }

        $credentials = [];

        foreach (Values::list($record->credentials) as $bindingWire) {
            $binding = IoHostCodec::decodeCredentialBinding($bindingWire);
            $credentials[] = new Credential($binding->name, $binding->reference->id);
        }

        $helpers = new Helpers($invocation, $credentials);
        $selections = array_map(static fn(ContractSelection $selection): Selection => new Selection($selection->key, $selection->value), $configuration);
        $request = new Request($publicContext, $selected->id, $selected->revision, $selections, $helpers, $invocation);
        $result = $plugin->enrich($request);
        $wireResult = (object) ['metadata' => array_map(AuthorValues::encodeMetadata(...), $result->metadata), 'assets' => []];

        foreach ($result->assets as $asset) {
            $wireResult->assets[] = (object) ['artifact' => AuthorValues::artifact($asset->artifact), 'derived-from' => $asset->derivedFrom,
                'activity' => $asset->activity, 'activity-version' => $asset->activityVersion];
        }

        return (object) ['ok' => $wireResult];
    }

    /**
     * Convert saved item context to the public value type.
     */
    private static function item(ItemContext $context): Item
    {
        return new Item($context->itemId, array_map(AuthorValues::asset(...), $context->assets), array_map(AuthorValues::metadata(...), $context->metadata));
    }
}
