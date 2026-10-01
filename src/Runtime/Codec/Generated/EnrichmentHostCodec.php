<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\EnrichmentHost\AssetError;
use Stashd\PluginSdk\Contract\EnrichmentHost\AssetErrorDenied;
use Stashd\PluginSdk\Contract\EnrichmentHost\AssetErrorFailed;
use Stashd\PluginSdk\Contract\EnrichmentHost\AssetErrorMissing;
use Stashd\PluginSdk\Contract\EnrichmentHost\ItemContext;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Exact focused value codec for frozen enrichment-host declarations.
 */
final class EnrichmentHostCodec
{
    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeItemContext(mixed $value): ItemContext
    {
        $record = Values::record($value, ['item-id', 'assets', 'metadata']);

        return new ItemContext(
            Values::text($record->{'item-id'}),
            array_map(static fn(mixed $element) => IoHostCodec::decodePreservedAsset($element), Values::list($record->{'assets'})),
            array_map(static fn(mixed $element) => IoHostCodec::decodePluginMetadata($element), Values::list($record->{'metadata'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeItemContext(ItemContext $value): stdClass
    {
        return (object) [
            'item-id' => $value->itemId,
            'assets' => array_map(static fn(PreservedAsset $element) => IoHostCodec::encodePreservedAsset($element), $value->assets),
            'metadata' => array_map(static fn(PluginMetadata $element) => IoHostCodec::encodePluginMetadata($element), $value->metadata),
        ];
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeAssetError(mixed $value): AssetError
    {
        if (is_string($value)) {
            return match ($value) {
                'denied' => new AssetErrorDenied(),
                'missing' => new AssetErrorMissing(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'failed' => new AssetErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Encode only canonical concrete branches, rejecting foreign implementations of the union.
     */
    public static function encodeAssetError(AssetError $value): string|stdClass
    {
        return match (true) {
            $value instanceof AssetErrorDenied => 'denied',
            $value instanceof AssetErrorMissing => 'missing',
            $value instanceof AssetErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
