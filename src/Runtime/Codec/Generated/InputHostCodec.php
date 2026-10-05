<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\InputHost\CredentialError;
use Stashd\PluginSdk\Contract\InputHost\CredentialErrorDenied;
use Stashd\PluginSdk\Contract\InputHost\CredentialErrorUnavailable;
use Stashd\PluginSdk\Contract\InputHost\Deficiency;
use Stashd\PluginSdk\Contract\InputHost\DeficiencyDisposition;
use Stashd\PluginSdk\Contract\InputHost\DiscoveredItem;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryBatch;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryCommitError;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryCommitErrorRejected;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryContinuation;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryFinish;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryFinishExhaustive;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryFinishIndeterminate;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryFinishPartial;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryProgress;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryProgressFinished;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryProgressMore;
use Stashd\PluginSdk\Contract\InputHost\DiscoveryRefreshState;
use Stashd\PluginSdk\Contract\InputHost\InputDelegation;
use Stashd\PluginSdk\Contract\InputHost\OutcomeDiagnostic;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts JSON values to and from input-host declarations.
 */
final class InputHostCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDeficiency(mixed $value): Deficiency
    {
        $record = Values::record($value, ['disposition', 'diagnostic']);

        return new Deficiency(
            InputHostCodec::decodeDeficiencyDisposition($record->{'disposition'}),
            InputHostCodec::decodeOutcomeDiagnostic($record->{'diagnostic'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDeficiency(Deficiency $value): stdClass
    {
        return (object) [
            'disposition' => InputHostCodec::encodeDeficiencyDisposition($value->disposition),
            'diagnostic' => InputHostCodec::encodeOutcomeDiagnostic($value->diagnostic),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDiscoveredItem(mixed $value): DiscoveredItem
    {
        $record = Values::record($value, ['id', 'reference', 'delegation', 'size-bytes', 'size-estimated', 'metadata']);

        return new DiscoveredItem(
            Values::text($record->{'id'}),
            Values::text($record->{'reference'}),
            ($record->{'delegation'} === null ? null : InputHostCodec::decodeInputDelegation($record->{'delegation'})),
            ($record->{'size-bytes'} === null ? null : Values::unsigned($record->{'size-bytes'})),
            Values::boolean($record->{'size-estimated'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDiscoveredItem(DiscoveredItem $value): stdClass
    {
        return (object) [
            'id' => $value->id,
            'reference' => $value->reference,
            'delegation' => ($value->delegation === null ? null : InputHostCodec::encodeInputDelegation($value->delegation)),
            'size-bytes' => ($value->sizeBytes === null ? null : $value->sizeBytes),
            'size-estimated' => $value->sizeEstimated,
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDiscoveryBatch(mixed $value): DiscoveryBatch
    {
        $record = Values::record($value, ['items', 'progress']);

        return new DiscoveryBatch(
            array_map(static fn(mixed $element) => InputHostCodec::decodeDiscoveredItem($element), Values::list($record->{'items'})),
            InputHostCodec::decodeDiscoveryProgress($record->{'progress'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDiscoveryBatch(DiscoveryBatch $value): stdClass
    {
        return (object) [
            'items' => array_map(static fn(DiscoveredItem $element) => InputHostCodec::encodeDiscoveredItem($element), $value->items),
            'progress' => InputHostCodec::encodeDiscoveryProgress($value->progress),
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDiscoveryContinuation(mixed $value): DiscoveryContinuation
    {
        $record = Values::record($value, ['value']);

        return new DiscoveryContinuation(
            Values::text($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDiscoveryContinuation(DiscoveryContinuation $value): stdClass
    {
        return (object) [
            'value' => $value->value,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeDiscoveryRefreshState(mixed $value): DiscoveryRefreshState
    {
        $record = Values::record($value, ['value']);

        return new DiscoveryRefreshState(
            Values::text($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeDiscoveryRefreshState(DiscoveryRefreshState $value): stdClass
    {
        return (object) [
            'value' => $value->value,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeInputDelegation(mixed $value): InputDelegation
    {
        $record = Values::record($value, ['reference']);

        return new InputDelegation(
            Values::text($record->{'reference'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeInputDelegation(InputDelegation $value): stdClass
    {
        return (object) [
            'reference' => $value->reference,
        ];
    }

    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeOutcomeDiagnostic(mixed $value): OutcomeDiagnostic
    {
        $record = Values::record($value, ['message', 'evidence']);

        return new OutcomeDiagnostic(
            Values::text($record->{'message'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'evidence'})),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeOutcomeDiagnostic(OutcomeDiagnostic $value): stdClass
    {
        return (object) [
            'message' => $value->message,
            'evidence' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->evidence),
        ];
    }

    /**
     * Read a supported enum value.
     */
    public static function decodeDeficiencyDisposition(mixed $value): DeficiencyDisposition
    {
        return DeficiencyDisposition::tryFrom(Values::text($value)) ?? throw new ProtocolViolation('Unknown enum case');
    }

    /**
     * Write the enum value expected by the host.
     */
    public static function encodeDeficiencyDisposition(DeficiencyDisposition $value): string
    {
        return $value->value;
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeCredentialError(mixed $value): CredentialError
    {
        if (is_string($value)) {
            return match ($value) {
                'denied' => new CredentialErrorDenied(),
                'unavailable' => new CredentialErrorUnavailable(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodeCredentialError(CredentialError $value): string
    {
        return match (true) {
            $value instanceof CredentialErrorDenied => 'denied',
            $value instanceof CredentialErrorUnavailable => 'unavailable',
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeDiscoveryCommitError(mixed $value): DiscoveryCommitError
    {
        if (is_string($value)) {
            return match ($value) {
                'rejected' => new DiscoveryCommitErrorRejected(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodeDiscoveryCommitError(DiscoveryCommitError $value): string
    {
        return match (true) {
            $value instanceof DiscoveryCommitErrorRejected => 'rejected',
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeDiscoveryFinish(mixed $value): DiscoveryFinish
    {
        if (is_string($value)) {
            return match ($value) {
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'exhaustive' => new DiscoveryFinishExhaustive(($record->value === null ? null : InputHostCodec::decodeDiscoveryRefreshState($record->value))),
            'partial' => new DiscoveryFinishPartial(array_map(static fn(mixed $element) => InputHostCodec::decodeDeficiency($element), Values::list($record->value))),
            'indeterminate' => new DiscoveryFinishIndeterminate(InputHostCodec::decodeOutcomeDiagnostic($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodeDiscoveryFinish(DiscoveryFinish $value): stdClass
    {
        return match (true) {
            $value instanceof DiscoveryFinishExhaustive => (object) ['tag' => 'exhaustive', 'value' => ($value->value === null ? null : InputHostCodec::encodeDiscoveryRefreshState($value->value))],
            $value instanceof DiscoveryFinishPartial => (object) ['tag' => 'partial', 'value' => array_map(static fn(Deficiency $element) => InputHostCodec::encodeDeficiency($element), $value->value)],
            $value instanceof DiscoveryFinishIndeterminate => (object) ['tag' => 'indeterminate', 'value' => InputHostCodec::encodeOutcomeDiagnostic($value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeDiscoveryProgress(mixed $value): DiscoveryProgress
    {
        if (is_string($value)) {
            return match ($value) {
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'more' => new DiscoveryProgressMore(InputHostCodec::decodeDiscoveryContinuation($record->value)),
            'finished' => new DiscoveryProgressFinished(InputHostCodec::decodeDiscoveryFinish($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodeDiscoveryProgress(DiscoveryProgress $value): stdClass
    {
        return match (true) {
            $value instanceof DiscoveryProgressMore => (object) ['tag' => 'more', 'value' => InputHostCodec::encodeDiscoveryContinuation($value->value)],
            $value instanceof DiscoveryProgressFinished => (object) ['tag' => 'finished', 'value' => InputHostCodec::encodeDiscoveryFinish($value->value)],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
