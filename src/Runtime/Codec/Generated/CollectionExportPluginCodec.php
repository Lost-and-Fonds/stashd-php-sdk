<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\CollectionExportPlugin\Collection;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\CollectionEntry;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\ExportedArtifact;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\OptionValue;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\OptionValueBoolean;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\OptionValueNumber;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\OptionValueText;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginError;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorAuthentication;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorFailed;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorInvalidData;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorLimitExceeded;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorNotFound;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorRateLimited;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorUnavailable;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\PluginErrorUnsupported;
use Stashd\PluginSdk\Contract\CollectionExportPlugin\Setting;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts JSON values to and from collection-export-plugin declarations.
 */
final class CollectionExportPluginCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeCollection(mixed $value): Collection
    {
        $record = Values::record($value, ['title', 'entries']);

        return new Collection(
            ($record->{'title'} === null ? null : Values::text($record->{'title'})),
            array_map(static fn(mixed $element) => CollectionExportPluginCodec::decodeCollectionEntry($element), Values::list($record->{'entries'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeCollection(Collection $value): stdClass
    {
        return (object) [
            'title' => ($value->title === null ? null : $value->title),
            'entries' => array_map(static fn(CollectionEntry $element) => CollectionExportPluginCodec::encodeCollectionEntry($element), $value->entries),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeCollectionEntry(mixed $value): CollectionEntry
    {
        $record = Values::record($value, ['reference', 'title']);

        return new CollectionEntry(
            Values::text($record->{'reference'}),
            ($record->{'title'} === null ? null : Values::text($record->{'title'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeCollectionEntry(CollectionEntry $value): stdClass
    {
        return (object) [
            'reference' => $value->reference,
            'title' => ($value->title === null ? null : $value->title),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeExportedArtifact(mixed $value): ExportedArtifact
    {
        $record = Values::record($value, ['filename', 'media-type', 'contents']);

        return new ExportedArtifact(
            Values::text($record->{'filename'}),
            Values::text($record->{'media-type'}),
            array_map(static fn(mixed $element) => Values::integer('u8', $element), Values::list($record->{'contents'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeExportedArtifact(ExportedArtifact $value): stdClass
    {
        return (object) [
            'filename' => $value->filename,
            'media-type' => $value->mediaType,
            'contents' => array_map(static fn(int $element) => $element, $value->contents),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeSetting(mixed $value): Setting
    {
        $record = Values::record($value, ['key', 'value']);

        return new Setting(
            Values::text($record->{'key'}),
            CollectionExportPluginCodec::decodeOptionValue($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeSetting(Setting $value): stdClass
    {
        return (object) [
            'key' => $value->key,
            'value' => CollectionExportPluginCodec::encodeOptionValue($value->value),
        ];
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
            'authentication' => new PluginErrorAuthentication(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'rate-limited' => new PluginErrorRateLimited(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'unavailable' => new PluginErrorUnavailable(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'invalid-data' => new PluginErrorInvalidData(PluginTypesCodec::decodePluginErrorDetail($record->value)),
            'limit-exceeded' => new PluginErrorLimitExceeded(PluginTypesCodec::decodePluginErrorDetail($record->value)),
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
            $value instanceof PluginErrorInvalidData => (object) ['tag' => 'invalid-data', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorLimitExceeded => (object) ['tag' => 'limit-exceeded', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            $value instanceof PluginErrorFailed => (object) ['tag' => 'failed', 'value' => PluginTypesCodec::encodePluginErrorDetail($value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
