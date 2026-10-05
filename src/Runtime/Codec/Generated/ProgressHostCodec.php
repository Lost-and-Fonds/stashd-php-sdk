<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\ProgressHost\Progress;
use Stashd\PluginSdk\Runtime\Codec\Values;
use stdClass;

/**
 * Converts JSON values to and from progress-host declarations.
 */
final class ProgressHostCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
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
     * Write the field names expected by the host.
     */
    public static function encodeProgress(Progress $value): stdClass
    {
        return (object) [
            'stage' => $value->stage,
            'fraction' => ($value->fraction === null ? null : $value->fraction),
        ];
    }

}
