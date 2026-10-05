<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\InputHost\Deficiency;
use Stashd\PluginSdk\Contract\InputPlugin\AcquisitionOptions;
use Stashd\PluginSdk\Contract\InputPlugin\AcquisitionResult;
use Stashd\PluginSdk\Contract\InputPlugin\DiscoveryIntent;
use Stashd\PluginSdk\Contract\InputPlugin\DiscoveryRequest;
use Stashd\PluginSdk\Contract\InputPlugin\InputOption;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValue;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValueBoolean;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValueNumber;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValueText;
use Stashd\PluginSdk\Contract\InputPlugin\PluginError;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorAuthentication;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorCredentialDenied;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorCredentialUnavailable;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorFailed;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorInvalidData;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorNotFound;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorRateLimited;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorUnavailable;
use Stashd\PluginSdk\Contract\InputPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Contract\InputPlugin\PreservationOutcome;
use Stashd\PluginSdk\Contract\InputPlugin\PreservationOutcomeComplete;
use Stashd\PluginSdk\Contract\InputPlugin\PreservationOutcomePartial;
use Stashd\PluginSdk\Contract\InputPlugin\ResolvedInput;
use Stashd\PluginSdk\Contract\InputPlugin\SourceValue;
use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts JSON values to and from input-plugin declarations.
 */
final class InputPluginCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeAcquisitionOptions(mixed $value): AcquisitionOptions
    {
        $record = Values::record($value, ['options', 'credentials']);

        return new AcquisitionOptions(
            array_map(static fn(mixed $element) => InputPluginCodec::decodeInputOption($element), Values::list($record->{'options'})),
            array_map(static fn(mixed $element) => IoHostCodec::decodeCredentialBinding($element), Values::list($record->{'credentials'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeAcquisitionOptions(AcquisitionOptions $value): stdClass
    {
        return (object) [
            'options' => array_map(static fn(InputOption $element) => InputPluginCodec::encodeInputOption($element), $value->options),
            'credentials' => array_map(static fn(CredentialBinding $element) => IoHostCodec::encodeCredentialBinding($element), $value->credentials),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeAcquisitionResult(mixed $value): AcquisitionResult
    {
        $record = Values::record($value, ['artifacts', 'outcome']);

        return new AcquisitionResult(
            array_map(static fn(mixed $element) => IoHostCodec::decodeStagedArtifact($element), Values::list($record->{'artifacts'})),
            InputPluginCodec::decodePreservationOutcome($record->{'outcome'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeAcquisitionResult(AcquisitionResult $value): stdClass
    {
        return (object) [
            'artifacts' => array_map(static fn(StagedArtifact $element) => IoHostCodec::encodeStagedArtifact($element), $value->artifacts),
            'outcome' => InputPluginCodec::encodePreservationOutcome($value->outcome),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDiscoveryRequest(mixed $value): DiscoveryRequest
    {
        $record = Values::record($value, ['input-id', 'intent', 'options', 'continuation', 'refresh-state', 'maximum-items-per-batch']);

        return new DiscoveryRequest(
            Values::text($record->{'input-id'}),
            InputPluginCodec::decodeDiscoveryIntent($record->{'intent'}),
            array_map(static fn(mixed $element) => InputPluginCodec::decodeInputOption($element), Values::list($record->{'options'})),
            ($record->{'continuation'} === null ? null : InputHostCodec::decodeDiscoveryContinuation($record->{'continuation'})),
            ($record->{'refresh-state'} === null ? null : InputHostCodec::decodeDiscoveryRefreshState($record->{'refresh-state'})),
            Values::integer('u32', $record->{'maximum-items-per-batch'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDiscoveryRequest(DiscoveryRequest $value): stdClass
    {
        return (object) [
            'input-id' => $value->inputId,
            'intent' => InputPluginCodec::encodeDiscoveryIntent($value->intent),
            'options' => array_map(static fn(InputOption $element) => InputPluginCodec::encodeInputOption($element), $value->options),
            'continuation' => ($value->continuation === null ? null : InputHostCodec::encodeDiscoveryContinuation($value->continuation)),
            'refresh-state' => ($value->refreshState === null ? null : InputHostCodec::encodeDiscoveryRefreshState($value->refreshState)),
            'maximum-items-per-batch' => $value->maximumItemsPerBatch,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeInputOption(mixed $value): InputOption
    {
        $record = Values::record($value, ['key', 'value']);

        return new InputOption(
            Values::text($record->{'key'}),
            InputPluginCodec::decodeOptionValue($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeInputOption(InputOption $value): stdClass
    {
        return (object) [
            'key' => $value->key,
            'value' => InputPluginCodec::encodeOptionValue($value->value),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeResolvedInput(mixed $value): ResolvedInput
    {
        $record = Values::record($value, ['id', 'canonical-reference', 'estimated-item-count', 'size-bytes', 'size-estimated', 'metadata']);

        return new ResolvedInput(
            Values::text($record->{'id'}),
            ($record->{'canonical-reference'} === null ? null : Values::text($record->{'canonical-reference'})),
            ($record->{'estimated-item-count'} === null ? null : Values::integer('u32', $record->{'estimated-item-count'})),
            ($record->{'size-bytes'} === null ? null : Values::unsigned($record->{'size-bytes'})),
            Values::boolean($record->{'size-estimated'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeResolvedInput(ResolvedInput $value): stdClass
    {
        return (object) [
            'id' => $value->id,
            'canonical-reference' => ($value->canonicalReference === null ? null : $value->canonicalReference),
            'estimated-item-count' => ($value->estimatedItemCount === null ? null : $value->estimatedItemCount),
            'size-bytes' => ($value->sizeBytes === null ? null : $value->sizeBytes),
            'size-estimated' => $value->sizeEstimated,
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeSourceValue(mixed $value): SourceValue
    {
        $record = Values::record($value, ['key', 'value']);

        return new SourceValue(
            Values::text($record->{'key'}),
            InputPluginCodec::decodeOptionValue($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeSourceValue(SourceValue $value): stdClass
    {
        return (object) [
            'key' => $value->key,
            'value' => InputPluginCodec::encodeOptionValue($value->value),
        ];
    }

    /**
     * Read a supported enum value.
     */
    public static function decodeDiscoveryIntent(mixed $value): DiscoveryIntent
    {
        return DiscoveryIntent::tryFrom(Values::text($value)) ?? throw new ProtocolViolation('Unknown enum case');
    }

    /**
     * Write the enum value expected by the host.
     */
    public static function encodeDiscoveryIntent(DiscoveryIntent $value): string
    {
        return $value->value;
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeOptionValue(mixed $value): OptionValue
    {
        if (is_string($value)) {
            return match ($value) {
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'boolean' => new OptionValueBoolean(Values::boolean($record->value)),
            'number' => new OptionValueNumber(Values::signed($record->value)),
            'text' => new OptionValueText(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodeOptionValue(OptionValue $value): stdClass
    {
        return match (true) {
            $value instanceof OptionValueBoolean => (object) ['tag' => 'boolean', 'value' => $value->value],
            $value instanceof OptionValueNumber => (object) ['tag' => 'number', 'value' => (string) $value->value],
            $value instanceof OptionValueText => (object) ['tag' => 'text', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
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
            'credential-denied' => new PluginErrorCredentialDenied(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'credential-unavailable' => new PluginErrorCredentialUnavailable(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'authentication' => new PluginErrorAuthentication(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'rate-limited' => new PluginErrorRateLimited(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'unavailable' => new PluginErrorUnavailable(PluginTypesCodec::decodePluginErrorDetail($record->value)),
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
            $value instanceof PluginErrorCredentialDenied => (object) ['tag' => 'credential-denied', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorCredentialUnavailable => (object) ['tag' => 'credential-unavailable', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorAuthentication => (object) ['tag' => 'authentication', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorRateLimited => (object) ['tag' => 'rate-limited', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorUnavailable => (object) ['tag' => 'unavailable', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorInvalidData => (object) ['tag' => 'invalid-data', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorFailed => (object) ['tag' => 'failed', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodePreservationOutcome(mixed $value): PreservationOutcome
    {
        if (is_string($value)) {
            return match ($value) {
                'complete' => new PreservationOutcomeComplete(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'partial' => new PreservationOutcomePartial(array_map(static fn(mixed $element) => InputHostCodec::decodeDeficiency($element), Values::list($record->value))),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodePreservationOutcome(PreservationOutcome $value): string|stdClass
    {
        return match (true) {
            $value instanceof PreservationOutcomeComplete => 'complete',
            $value instanceof PreservationOutcomePartial => (object) ['tag' => 'partial', 'value' => array_map(static fn(Deficiency $element) => InputHostCodec::encodeDeficiency($element), $value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
