<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec\Generated;

use Stashd\PluginSdk\Contract\HttpHost\HttpError;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorAuthenticationRejected;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorBodyFailed;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorCredentialUnavailable;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorDenied;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorFailed;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorRateLimited;
use Stashd\PluginSdk\Contract\HttpHost\HttpErrorUnavailable;
use Stashd\PluginSdk\Contract\HttpHost\HttpHeader;
use Stashd\PluginSdk\Runtime\Codec\Values;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Converts JSON values to and from http-host declarations.
 */
final class HttpHostCodec
{
    /**
     * Read the expected fields, rejecting missing or extra fields.
     */
    public static function decodeHttpHeader(mixed $value): HttpHeader
    {
        $record = Values::record($value, ['name', 'value']);

        return new HttpHeader(
            Values::text($record->{'name'}),
            Values::text($record->{'value'}),
        );
    }

    /**
     * Write the field names expected by the host.
     */
    public static function encodeHttpHeader(HttpHeader $value): stdClass
    {
        return (object) [
            'name' => $value->name,
            'value' => $value->value,
        ];
    }

    /**
     * Decode a payloadless string or exact tagged payload, never accepting extra fields.
     */
    public static function decodeHttpError(mixed $value): HttpError
    {
        if (is_string($value)) {
            return match ($value) {
                'denied' => new HttpErrorDenied(),
                'credential-unavailable' => new HttpErrorCredentialUnavailable(),
                'authentication-rejected' => new HttpErrorAuthenticationRejected(),
                'rate-limited' => new HttpErrorRateLimited(),
                default => throw new ProtocolViolation('Unknown payloadless variant case'),
            };
        }

        $record = Values::record($value, ['tag', 'value']);

        return match (Values::text($record->tag)) {
            'body-failed' => new HttpErrorBodyFailed(Values::text($record->value)),
            'unavailable' => new HttpErrorUnavailable(Values::text($record->value)),
            'failed' => new HttpErrorFailed(Values::text($record->value)),
            default => throw new ProtocolViolation('Unknown payload-bearing variant case'),
        };
    }

    /**
     * Write a supported result type, rejecting unrecognized implementations.
     */
    public static function encodeHttpError(HttpError $value): string|stdClass
    {
        return match (true) {
            $value instanceof HttpErrorDenied => 'denied',
            $value instanceof HttpErrorCredentialUnavailable => 'credential-unavailable',
            $value instanceof HttpErrorAuthenticationRejected => 'authentication-rejected',
            $value instanceof HttpErrorRateLimited => 'rate-limited',
            $value instanceof HttpErrorBodyFailed => (object) ['tag' => 'body-failed', 'value' => $value->value],
            $value instanceof HttpErrorUnavailable => (object) ['tag' => 'unavailable', 'value' => $value->value],
            $value instanceof HttpErrorFailed => (object) ['tag' => 'failed', 'value' => $value->value],
            default => throw new ProtocolViolation('Foreign variant implementation'),
        };
    }

}
