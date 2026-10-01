<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\BroadcastHost\CollectionReadError;
use Stashd\PluginSdk\Contract\BroadcastHost\CollectionReadErrorFailed;
use Stashd\PluginSdk\Contract\BroadcastHost\CollectionReadErrorLimitExceeded;
use Stashd\PluginSdk\Contract\BroadcastHost\CollectionReadErrorRejected;
use Stashd\PluginSdk\Contract\BroadcastHost\CollectionReadErrorUnavailable;
use Stashd\PluginSdk\Contract\BroadcastHost\Item;
use Stashd\PluginSdk\Contract\BroadcastHost\PublicationReportError;
use Stashd\PluginSdk\Contract\BroadcastHost\PublicationReportErrorFailed;
use Stashd\PluginSdk\Contract\BroadcastHost\PublicationReportErrorLimitExceeded;
use Stashd\PluginSdk\Contract\BroadcastHost\PublicationReportErrorRejected;
use Stashd\PluginSdk\Contract\BroadcastHost\PublishedFile;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Exact focused value codec for frozen broadcast-host declarations.
 */
final class BroadcastHostCodec
{
    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeItem(mixed $value): Item
    {
        $record = Values::record($value, ['id', 'assets', 'metadata']);

        return new Item(
            Values::text($record->{'id'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePreservedAsset($element), Values::list($record->{'assets'})),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeItem(Item $value): stdClass
    {
        return (object) [
            'id' => $value->id,
            'assets' => array_map(static fn(PreservedAsset $element) => IoHostCodec::encodePreservedAsset($element), $value->assets),
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
        ];
    }

    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodePublishedFile(mixed $value): PublishedFile
    {
        $record = Values::record($value, ['item-id', 'asset-id', 'relative-path']);

        return new PublishedFile(
            ($record->{'item-id'} === null ? null : Values::text($record->{'item-id'})),
            ($record->{'asset-id'} === null ? null : Values::text($record->{'asset-id'})),
            Values::text($record->{'relative-path'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodePublishedFile(PublishedFile $value): stdClass
    {
        return (object) [
            'item-id' => ($value->itemId === null ? null : $value->itemId),
            'asset-id' => ($value->assetId === null ? null : $value->assetId),
            'relative-path' => $value->relativePath,
        ];
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeCollectionReadError(mixed $value): CollectionReadError
    {
        if (is_string($value)) {
            return match ($value) {
                'rejected' => new CollectionReadErrorRejected(),
                'limit-exceeded' => new CollectionReadErrorLimitExceeded(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'unavailable' => new CollectionReadErrorUnavailable(Values::text($record->value)),
            'failed' => new CollectionReadErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodeCollectionReadError(CollectionReadError $value): string|stdClass
    {
        return match (true) {
            $value instanceof CollectionReadErrorRejected => 'rejected',
            $value instanceof CollectionReadErrorLimitExceeded => 'limit-exceeded',
            $value instanceof CollectionReadErrorUnavailable => (object) ['tag' => 'unavailable', 'value' => $value->value],
            $value instanceof CollectionReadErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodePublicationReportError(mixed $value): PublicationReportError
    {
        if (is_string($value)) {
            return match ($value) {
                'rejected' => new PublicationReportErrorRejected(),
                'limit-exceeded' => new PublicationReportErrorLimitExceeded(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'failed' => new PublicationReportErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodePublicationReportError(PublicationReportError $value): string|stdClass
    {
        return match (true) {
            $value instanceof PublicationReportErrorRejected => 'rejected',
            $value instanceof PublicationReportErrorLimitExceeded => 'limit-exceeded',
            $value instanceof PublicationReportErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
