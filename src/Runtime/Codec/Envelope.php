<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Validates exact RPC envelopes independently of typed lifecycle values.
 */
final class Envelope
{
    /**
     * Require all and only the declared object members, irrespective of key ordering.
     * @param list<string> $fields
     */
    public static function exact(stdClass $value, array $fields): void
    {
        $actual = array_keys(get_object_vars($value));
        sort($actual);
        sort($fields);

        if ($actual !== $fields) {
            throw new ProtocolViolation('Object contains missing or unknown members');
        }
    }

    /**
     * Validate a lifecycle request without accepting hello or private method aliases.
     */
    public static function request(stdClass $frame): void
    {
        self::exact($frame, ['protocol', 'id', 'kind', 'invocation', 'method', 'params']);
        self::common($frame, 'request');

        if (!is_string($frame->invocation) || !is_string($frame->method)
            || !str_starts_with($frame->method, 'stashd:plugin/') || !$frame->params instanceof stdClass) {
            throw new ProtocolViolation('Invalid lifecycle invocation, method or parameters');
        }
    }

    /**
     * Require exact correlation; never skip a mismatched response in search of another.
     */
    public static function response(stdClass $frame, string $id, ?string $invocation): void
    {
        self::exact($frame, $invocation === null
            ? ['protocol', 'id', 'kind', 'result']
            : ['protocol', 'id', 'kind', 'invocation', 'result']);
        self::common($frame, 'response');

        if ($frame->id !== $id || ($invocation !== null && $frame->invocation !== $invocation)) {
            throw new ProtocolViolation('Response correlation or invocation does not match outstanding call');
        }
    }

    /**
     * Validate a host hello result and return its independent receive maximum.
     */
    public static function hello(stdClass $frame, string $id): int
    {
        self::response($frame, $id, null);
        $result = $frame->result;

        if (!$result instanceof stdClass) {
            throw new ProtocolViolation('Hello result must be an object');
        }

        self::exact($result, ['protocol', 'min', 'max', 'max-frame-bytes']);
        $maximum = $result->{'max-frame-bytes'};

        if ($result->protocol !== 1 || $result->min !== 1 || $result->max !== 1
            || !is_int($maximum) || $maximum < 4096 || $maximum > 4294967295) {
            throw new ProtocolViolation('Invalid hello version or receive advertisement');
        }

        return $maximum;
    }

    /**
     * Validate common fields using strict types, not PHP scalar coercion.
     */
    private static function common(stdClass $frame, string $kind): void
    {
        if ($frame->protocol !== 1 || $frame->kind !== $kind || !is_string($frame->id) || $frame->id === '') {
            throw new ProtocolViolation('Invalid RPC protocol, kind or correlation ID');
        }
    }
}
