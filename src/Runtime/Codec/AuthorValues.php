<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Broadcast\Item;
use Stashd\PluginSdk\Contract\IoHost\PluginMetadata;
use Stashd\PluginSdk\Contract\IoHost\PreservedAsset;
use Stashd\PluginSdk\Helper\Artifact;
use Stashd\PluginSdk\Runtime\Codec\Generated\BroadcastHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\IoHostCodec;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Shared\Asset;
use Stashd\PluginSdk\Shared\Metadata;
use stdClass;

/**
 * Maps public assets and metadata without exposing contract types to plugins.
 */
final class AuthorValues
{
    /**
     * Convert a host-supplied metadata facet to a public value.
     */
    public static function metadata(PluginMetadata $value): Metadata
    {
        return new Metadata($value->schema, $value->json);
    }

    /**
     * Convert a plugin-owned metadata facet to its transport value.
     */
    public static function encodeMetadata(Metadata $value): stdClass
    {
        return IoHostCodec::encodePluginMetadata(new PluginMetadata($value->schema, $value->json));
    }


    /**
     * Decode one selected item into author-facing values.
     */
    public static function broadcastItem(mixed $value): Item
    {
        $item = BroadcastHostCodec::decodeItem($value);

        return new Item(
            $item->id,
            array_map(static fn(PreservedAsset $asset): Asset => self::asset($asset), $item->assets),
            array_map(static fn(PluginMetadata $metadata): Metadata => self::metadata($metadata), $item->metadata),
        );
    }

    /**
     * Decode one saved file granted to the current call.
     */
    public static function asset(PreservedAsset $value): Asset
    {
        return new Asset($value->id, $value->reference, $value->mediaType, self::authorSize($value->sizeBytes), array_map(self::metadata(...), $value->metadata));
    }

    /**
     * Require finished output produced by this call before returning it.
     */
    public static function artifact(Artifact $value): stdClass
    {
        return IoHostCodec::encodeStagedArtifact($value->receipt());
    }

    /**
     * Check a byte size without narrowing large unsigned counts.
     */
    public static function size(?int $value): ?string
    {
        if ($value !== null && $value < 0) {
            throw new ProtocolViolation('Byte size cannot be negative');
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * Convert an exact contract byte count when PHP can represent it.
     */
    public static function authorSize(string $value): int
    {
        if (strlen($value) > strlen((string) PHP_INT_MAX) || (strlen($value) === strlen((string) PHP_INT_MAX) && strcmp($value, (string) PHP_INT_MAX) > 0)) {
            throw new ProtocolViolation('Byte size exceeds the PHP integer limit');
        }

        return (int) $value;
    }
}
