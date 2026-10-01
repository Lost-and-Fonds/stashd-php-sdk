<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\PluginTypes\PluginErrorDetail;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;

/**
 * Exact focused value codec for frozen plugin-types declarations.
 */
final class PluginTypesCodec
{
    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodePluginErrorDetail(mixed $value): PluginErrorDetail
    {
        $record = Values::record($value, ['message', 'retryable']);

        return new PluginErrorDetail(
            Values::text($record->{'message'}),
            Values::boolean($record->{'retryable'}),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodePluginErrorDetail(PluginErrorDetail $value): stdClass
    {
        return (object) [
            'message' => $value->message,
            'retryable' => $value->retryable,
        ];
    }

}
