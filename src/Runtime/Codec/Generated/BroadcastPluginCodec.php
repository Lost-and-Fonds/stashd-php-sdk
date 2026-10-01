<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\BroadcastPlugin\Choice;
use Stashd\PluginSdk\Contract\BroadcastPlugin\DestinationConfiguration;
use Stashd\PluginSdk\Contract\BroadcastPlugin\FileReportStatus;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OperationRequest;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OperationResult;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValue;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueBoolean;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueNumber;
use Stashd\PluginSdk\Contract\BroadcastPlugin\OptionValueText;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginError;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorAuthentication;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorFailed;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorInvalidData;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorNotFound;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorRateLimited;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorUnavailable;
use Stashd\PluginSdk\Contract\BroadcastPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Contract\BroadcastPlugin\Publication;
use Stashd\PluginSdk\Contract\BroadcastPlugin\Setting;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Exact focused value codec for frozen broadcast-plugin declarations.
 */
final class BroadcastPluginCodec
{
    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeChoice(mixed $value): Choice
    {
        $record = Values::record($value, ['value', 'label']);

        return new Choice(
            Values::text($record->{'value'}),
            Values::text($record->{'label'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeChoice(Choice $value): stdClass
    {
        return (object) [
            'value' => $value->value,
            'label' => $value->label,
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeDestinationConfiguration(mixed $value): DestinationConfiguration
    {
        $record = Values::record($value, ['settings']);

        return new DestinationConfiguration(
            array_map(static fn(mixed $element) => BroadcastPluginCodec::decodeSetting($element), Values::list($record->{'settings'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeDestinationConfiguration(DestinationConfiguration $value): stdClass
    {
        return (object) [
            'settings' => array_map(static fn(Setting $element) => BroadcastPluginCodec::encodeSetting($element), $value->settings),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeOperationRequest(mixed $value): OperationRequest
    {
        $record = Values::record($value, ['name', 'settings', 'payload']);

        return new OperationRequest(
            Values::text($record->{'name'}),
            array_map(static fn(mixed $element) => BroadcastPluginCodec::decodeSetting($element), Values::list($record->{'settings'})),
            array_map(static fn(mixed $element) => BroadcastPluginCodec::decodeSetting($element), Values::list($record->{'payload'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeOperationRequest(OperationRequest $value): stdClass
    {
        return (object) [
            'name' => $value->name,
            'settings' => array_map(static fn(Setting $element) => BroadcastPluginCodec::encodeSetting($element), $value->settings),
            'payload' => array_map(static fn(Setting $element) => BroadcastPluginCodec::encodeSetting($element), $value->payload),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeOperationResult(mixed $value): OperationResult
    {
        $record = Values::record($value, ['choices', 'values']);

        return new OperationResult(
            array_map(static fn(mixed $element) => BroadcastPluginCodec::decodeChoice($element), Values::list($record->{'choices'})),
            array_map(static fn(mixed $element) => BroadcastPluginCodec::decodeSetting($element), Values::list($record->{'values'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeOperationResult(OperationResult $value): stdClass
    {
        return (object) [
            'choices' => array_map(static fn(Choice $element) => BroadcastPluginCodec::encodeChoice($element), $value->choices),
            'values' => array_map(static fn(Setting $element) => BroadcastPluginCodec::encodeSetting($element), $value->values),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodePublication(mixed $value): Publication
    {
        $record = Values::record($value, ['artifact', 'files']);

        return new Publication(
            ($record->{'artifact'} === null ? null : IoHostCodec::decodeStagedArtifact($record->{'artifact'})),
            BroadcastPluginCodec::decodeFileReportStatus($record->{'files'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodePublication(Publication $value): stdClass
    {
        return (object) [
            'artifact' => ($value->artifact === null ? null : IoHostCodec::encodeStagedArtifact($value->artifact)),
            'files' => BroadcastPluginCodec::encodeFileReportStatus($value->files),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeSetting(mixed $value): Setting
    {
        $record = Values::record($value, ['key', 'value']);

        return new Setting(
            Values::text($record->{'key'}),
            BroadcastPluginCodec::decodeOptionValue($record->{'value'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeSetting(Setting $value): stdClass
    {
        return (object) [
            'key' => $value->key,
            'value' => BroadcastPluginCodec::encodeOptionValue($value->value),
        ];
    }

    /**
     * Decode only a declared canonical enum spelling.
     */
    public static function decodeFileReportStatus(mixed $value): FileReportStatus
    {
        return FileReportStatus::tryFrom(Values::text($value)) ?? throw new ProtocolViolation('Unknown enum case');
    }

    /**
     * Encode the exact protocol identity of this enum case.
     */
    public static function encodeFileReportStatus(FileReportStatus $value): string
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
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
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
            'authentication' => new PluginErrorAuthentication(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'rate-limited' => new PluginErrorRateLimited(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'unavailable' => new PluginErrorUnavailable(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'invalid-data' => new PluginErrorInvalidData(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'failed' => new PluginErrorFailed(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodePluginError(PluginError $value): stdClass
    {
        return match (true) {
            $value instanceof PluginErrorUnsupported => (object) ['tag' => 'unsupported', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorNotFound => (object) ['tag' => 'not-found', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorAuthentication => (object) ['tag' => 'authentication', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorRateLimited => (object) ['tag' => 'rate-limited', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorUnavailable => (object) ['tag' => 'unavailable', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorInvalidData => (object) ['tag' => 'invalid-data', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorFailed => (object) ['tag' => 'failed', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
