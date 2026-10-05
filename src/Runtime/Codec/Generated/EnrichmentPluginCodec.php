<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\EnrichmentPlugin\Capability;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationChoice;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationOption;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\ConfigurationValue;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\DerivedAsset;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\EnrichmentResult;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginError;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorAuthentication;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorFailed;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorInvalidConfiguration;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorInvalidData;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorNotFound;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorRateLimited;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorUnavailable;
use Stashd\PluginSdk\Contract\EnrichmentPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts JSON values to and from enrichment-plugin declarations.
 */
final class EnrichmentPluginCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeCapability(mixed $value): Capability
    {
        $record = Values::record($value, ['id', 'revision', 'options']);

        return new Capability(
            Values::text($record->{'id'}),
            Values::text($record->{'revision'}),
            array_map(static fn(mixed $element) => EnrichmentPluginCodec::decodeConfigurationOption($element), Values::list($record->{'options'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeCapability(Capability $value): stdClass
    {
        return (object) [
            'id' => $value->id,
            'revision' => $value->revision,
            'options' => array_map(static fn(ConfigurationOption $element) => EnrichmentPluginCodec::encodeConfigurationOption($element), $value->options),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeConfigurationChoice(mixed $value): ConfigurationChoice
    {
        $record = Values::record($value, ['value', 'label']);

        return new ConfigurationChoice(
            Values::text($record->{'value'}),
            Values::text($record->{'label'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeConfigurationChoice(ConfigurationChoice $value): stdClass
    {
        return (object) [
            'value' => $value->value,
            'label' => $value->label,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeConfigurationOption(mixed $value): ConfigurationOption
    {
        $record = Values::record($value, ['key', 'label', 'required', 'choices']);

        return new ConfigurationOption(
            Values::text($record->{'key'}),
            Values::text($record->{'label'}),
            Values::boolean($record->{'required'}),
            array_map(static fn(mixed $element) => EnrichmentPluginCodec::decodeConfigurationChoice($element), Values::list($record->{'choices'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeConfigurationOption(ConfigurationOption $value): stdClass
    {
        return (object) [
            'key' => $value->key,
            'label' => $value->label,
            'required' => $value->required,
            'choices' => array_map(static fn(ConfigurationChoice $element) => EnrichmentPluginCodec::encodeConfigurationChoice($element), $value->choices),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeConfigurationValue(mixed $value): ConfigurationValue
    {
        $record = Values::record($value, ['key', 'value']);

        return new ConfigurationValue(
            Values::text($record->{'key'}),
            Values::text($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeConfigurationValue(ConfigurationValue $value): stdClass
    {
        return (object) [
            'key' => $value->key,
            'value' => $value->value,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDerivedAsset(mixed $value): DerivedAsset
    {
        $record = Values::record($value, ['artifact', 'derived-from', 'activity', 'activity-version']);

        return new DerivedAsset(
            IoHostCodec::decodeStagedArtifact($record->{'artifact'}),
            array_map(static fn(mixed $element) => Values::text($element), Values::list($record->{'derived-from'})),
            Values::text($record->{'activity'}),
            Values::text($record->{'activity-version'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDerivedAsset(DerivedAsset $value): stdClass
    {
        return (object) [
            'artifact' => IoHostCodec::encodeStagedArtifact($value->artifact),
            'derived-from' => array_map(static fn(string $element) => $element, $value->derivedFrom),
            'activity' => $value->activity,
            'activity-version' => $value->activityVersion,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeEnrichmentResult(mixed $value): EnrichmentResult
    {
        $record = Values::record($value, ['metadata', 'assets']);

        return new EnrichmentResult(
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
            array_map(static fn(mixed $element) => EnrichmentPluginCodec::decodeDerivedAsset($element), Values::list($record->{'assets'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeEnrichmentResult(EnrichmentResult $value): stdClass
    {
        return (object) [
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
            'assets' => array_map(static fn(DerivedAsset $element) => EnrichmentPluginCodec::encodeDerivedAsset($element), $value->assets),
        ];
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodePluginError(mixed $value): PluginError
    {
        if (is_string($value)) {
            return match ($value) {
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'unsupported' => new PluginErrorUnsupported(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'not-found' => new PluginErrorNotFound(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'authentication' => new PluginErrorAuthentication(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'rate-limited' => new PluginErrorRateLimited(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'unavailable' => new PluginErrorUnavailable(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'invalid-configuration' => new PluginErrorInvalidConfiguration(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'invalid-data' => new PluginErrorInvalidData(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'failed' => new PluginErrorFailed(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodePluginError(PluginError $value): stdClass
    {
        return match (true) {
            $value instanceof PluginErrorUnsupported => (object) ['tag' => 'unsupported', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorNotFound => (object) ['tag' => 'not-found', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorAuthentication => (object) ['tag' => 'authentication', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorRateLimited => (object) ['tag' => 'rate-limited', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorUnavailable => (object) ['tag' => 'unavailable', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorInvalidConfiguration => (object) ['tag' => 'invalid-configuration', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorInvalidData => (object) ['tag' => 'invalid-data', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorFailed => (object) ['tag' => 'failed', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
