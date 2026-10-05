<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\PluginTypes\PluginErrorDetail;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;

/**
 * Converts JSON values to and from plugin-types declarations.
 */
final class PluginTypesCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
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
     * Write the field names expected by the host.
     */
    public static function encodePluginErrorDetail(PluginErrorDetail $value): stdClass
    {
        return (object) [
            'message' => $value->message,
            'retryable' => $value->retryable,
        ];
    }

}
