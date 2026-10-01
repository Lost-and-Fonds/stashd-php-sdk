<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;
use Stashd\PluginSdk\Contract\IoHost\CredentialReference;
use Stashd\PluginSdk\Contract\IoHost\HelperError;
use Stashd\PluginSdk\Contract\IoHost\HelperErrorCredentialDenied;
use Stashd\PluginSdk\Contract\IoHost\HelperErrorCredentialUnavailable;
use Stashd\PluginSdk\Contract\IoHost\HelperErrorDenied;
use Stashd\PluginSdk\Contract\IoHost\HelperErrorFailed;
use Stashd\PluginSdk\Contract\IoHost\HelperErrorInputFailed;
use Stashd\PluginSdk\Contract\IoHost\HelperErrorUnavailable;
use Stashd\PluginSdk\Contract\IoHost\HelperResult;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;
use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;
use Stashd\PluginSdk\Contract\IoHost\StagingError;
use Stashd\PluginSdk\Contract\IoHost\StagingErrorDenied;
use Stashd\PluginSdk\Contract\IoHost\StagingErrorFailed;
use Stashd\PluginSdk\Contract\IoHost\StreamError;
use Stashd\PluginSdk\Contract\IoHost\StreamErrorDenied;
use Stashd\PluginSdk\Contract\IoHost\StreamErrorFailed;
use Stashd\PluginSdk\Contract\IoHost\StreamErrorMissing;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Exact focused value codec for frozen io-host declarations.
 */
final class IoHostCodec
{
    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeCredentialBinding(mixed $value): CredentialBinding
    {
        $record = Values::record($value, ['name', 'reference']);

        return new CredentialBinding(
            Values::text($record->{'name'}),
            IoHostCodec::decodeCredentialReference($record->{'reference'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeCredentialBinding(CredentialBinding $value): stdClass
    {
        return (object) [
            'name' => $value->name,
            'reference' => IoHostCodec::encodeCredentialReference($value->reference),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeCredentialReference(mixed $value): CredentialReference
    {
        $record = Values::record($value, ['id']);

        return new CredentialReference(
            Values::text($record->{'id'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeCredentialReference(CredentialReference $value): stdClass
    {
        return (object) [
            'id' => $value->id,
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeHelperResult(mixed $value): HelperResult
    {
        $record = Values::record($value, ['exit-code', 'stdout', 'stderr']);

        return new HelperResult(
            Values::integer('s32', $record->{'exit-code'}),
            Values::text($record->{'stdout'}),
            Values::text($record->{'stderr'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeHelperResult(HelperResult $value): stdClass
    {
        return (object) [
            'exit-code' => $value->exitCode,
            'stdout' => $value->stdout,
            'stderr' => $value->stderr,
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodePluginMetadata(mixed $value): PluginMetadata
    {
        $record = Values::record($value, ['schema', 'json']);

        return new PluginMetadata(
            Values::text($record->{'schema'}),
            Values::text($record->{'json'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodePluginMetadata(PluginMetadata $value): stdClass
    {
        return (object) [
            'schema' => $value->schema,
            'json' => $value->json,
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodePreservedAsset(mixed $value): PreservedAsset
    {
        $record = Values::record($value, ['id', 'reference', 'media-type', 'size-bytes', 'metadata']);

        return new PreservedAsset(
            Values::text($record->{'id'}),
            Values::text($record->{'reference'}),
            ($record->{'media-type'} === null ? null : Values::text($record->{'media-type'})),
            Values::unsigned($record->{'size-bytes'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodePreservedAsset(PreservedAsset $value): stdClass
    {
        return (object) [
            'id' => $value->id,
            'reference' => $value->reference,
            'media-type' => ($value->mediaType === null ? null : $value->mediaType),
            'size-bytes' => $value->sizeBytes->decimal,
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeStagedArtifact(mixed $value): StagedArtifact
    {
        $record = Values::record($value, ['reference', 'media-type', 'size-bytes', 'metadata']);

        return new StagedArtifact(
            Values::text($record->{'reference'}),
            ($record->{'media-type'} === null ? null : Values::text($record->{'media-type'})),
            Values::unsigned($record->{'size-bytes'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeStagedArtifact(StagedArtifact $value): stdClass
    {
        return (object) [
            'reference' => $value->reference,
            'media-type' => ($value->mediaType === null ? null : $value->mediaType),
            'size-bytes' => $value->sizeBytes->decimal,
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
        ];
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeHelperError(mixed $value): HelperError
    {
        if (is_string($value)) {
            return match ($value) {
                'denied' => new HelperErrorDenied(),
                'credential-denied' => new HelperErrorCredentialDenied(),
                'credential-unavailable' => new HelperErrorCredentialUnavailable(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'input-failed' => new HelperErrorInputFailed(Values::text($record->value)),
            'unavailable' => new HelperErrorUnavailable(Values::text($record->value)),
            'failed' => new HelperErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodeHelperError(HelperError $value): string|stdClass
    {
        return match (true) {
            $value instanceof HelperErrorDenied => 'denied',
            $value instanceof HelperErrorCredentialDenied => 'credential-denied',
            $value instanceof HelperErrorCredentialUnavailable => 'credential-unavailable',
            $value instanceof HelperErrorInputFailed => (object) ['tag' => 'input-failed', 'value' => $value->value],
            $value instanceof HelperErrorUnavailable => (object) ['tag' => 'unavailable', 'value' => $value->value],
            $value instanceof HelperErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeStagingError(mixed $value): StagingError
    {
        if (is_string($value)) {
            return match ($value) {
                'denied' => new StagingErrorDenied(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'failed' => new StagingErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodeStagingError(StagingError $value): string|stdClass
    {
        return match (true) {
            $value instanceof StagingErrorDenied => 'denied',
            $value instanceof StagingErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeStreamError(mixed $value): StreamError
    {
        if (is_string($value)) {
            return match ($value) {
                'denied' => new StreamErrorDenied(),
                'missing' => new StreamErrorMissing(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'failed' => new StreamErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodeStreamError(StreamError $value): string|stdClass
    {
        return match (true) {
            $value instanceof StreamErrorDenied => 'denied',
            $value instanceof StreamErrorMissing => 'missing',
            $value instanceof StreamErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
