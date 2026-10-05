<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Contract\InputPlugin\InputOption;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValueBoolean;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValueNumber;
use Stashd\PluginSdk\Contract\InputPlugin\OptionValueText;
use Stashd\PluginSdk\Input\AcquisitionResult;
use Stashd\PluginSdk\Input\DiscoveredItem;
use Stashd\PluginSdk\Input\Discovery;
use Stashd\PluginSdk\Input\Option;
use Stashd\PluginSdk\Input\ResolvedInput;
use Stashd\PluginSdk\Input\Source;
use Stashd\PluginSdk\Runtime\Codec\Generated\InputHostCodec;
use Stashd\PluginSdk\Runtime\Codec\Generated\InputPluginCodec;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts Input author values at the lifecycle boundary.
 */
final class InputAuthorCodec
{
    /**
     * Decode ordered source values without interpreting their keys.
     */
    public static function source(mixed $wire): Source
    {
        return new Source(array_map(static function (mixed $entry): Option {
            $value = InputPluginCodec::decodeSourceValue($entry);

            return new Option($value->key, self::option($value->value));
        }, Values::list($wire)));
    }

    /**
     * Decode ordered settings shared by discovery and acquisition.
     * @param list<InputOption> $wire
     */
    public static function options(array $wire): Source
    {
        return new Source(array_map(static fn(InputOption $value): Option => new Option($value->key, self::option($value->value)), $wire));
    }

    /**
     * Read the exact selected boolean, signed number or text.
     */
    private static function option(mixed $value): bool|int|string
    {
        return match (true) {
            $value instanceof OptionValueBoolean, $value instanceof OptionValueNumber, $value instanceof OptionValueText => $value->value,
            default => throw new ProtocolViolation('Unknown Input option type'),
        };
    }

    /**
     * Convert a resolved source to the host's result.
     */
    public static function resolved(ResolvedInput $value): stdClass
    {
        return (object) ['id' => Values::text($value->id),
            'canonical-reference' => $value->canonicalReference === null ? null : Values::text($value->canonicalReference),
            'estimated-item-count' => $value->estimatedItemCount === null ? null : Values::integer('u32', $value->estimatedItemCount),
            'size-bytes' => AuthorValues::size($value->sizeBytes), 'size-estimated' => $value->sizeEstimated,
            'metadata' => array_map(AuthorValues::encodeMetadata(...), $value->metadata)];
    }

    /**
     * Decode an independently acquired item, including delegation and metadata.
     */
    public static function item(mixed $wire): DiscoveredItem
    {
        $item = InputHostCodec::decodeDiscoveredItem($wire);

        return new DiscoveredItem($item->id, $item->reference, $item->delegation?->reference, $item->sizeBytes === null ? null : AuthorValues::authorSize($item->sizeBytes), $item->sizeEstimated, array_map(AuthorValues::metadata(...), $item->metadata));
    }

    /**
     * Convert saved files and their coverage into a successful acquisition result.
     */
    public static function acquired(AcquisitionResult $value): stdClass
    {
        return (object) ['artifacts' => array_map(AuthorValues::artifact(...), $value->artifacts),
            'outcome' => $value->complete ? 'complete' : (object) ['tag' => 'partial', 'value' => array_map(Discovery::deficiency(...), $value->deficiencies)]];
    }
}
