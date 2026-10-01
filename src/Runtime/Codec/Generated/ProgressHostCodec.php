<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\ProgressHost\Progress;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;

/**
 * Exact focused value codec for frozen progress-host declarations.
 */
final class ProgressHostCodec
{
    /**
     * Decode all and only the declared fields before constructing an immutable value.
     */
    public static function decodeProgress(mixed $value): Progress
    {
        $record = Values::record($value, ['stage', 'fraction']);

        return new Progress(
            Values::text($record->{'stage'}),
            ($record->{'fraction'} === null ? null : Values::floating('f64', $record->{'fraction'})),
        );
    }

    /**
     * Encode canonical field spellings without leaking PHP names into the wire.
     */
    public static function encodeProgress(Progress $value): stdClass
    {
        return (object) [
            'stage' => $value->stage,
            'fraction' => ($value->fraction === null ? null : $value->fraction),
        ];
    }

}
