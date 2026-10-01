<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use JsonException;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Parses interoperable JSON while rejecting duplicate decoded object names before interpretation.
 */
final class Json
{
    /**
     * Preserve objects separately from lists and never silently accept duplicate members.
     *
     * @return mixed
     */
    public static function decode(string $json): mixed
    {
        try {
            $value = json_decode($json, false, 2147483647, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new ProtocolViolation('Invalid UTF-8 JSON', previous: $error);
        }

        $offset = 0;
        self::scan($json, $offset);

        return $value;
    }

    /**
     * Encode UTF-8 JSON without lossy numeric conversion or unsupported values.
     */
    public static function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES, 2147483647);
        } catch (JsonException $error) {
            throw new ProtocolViolation('Value cannot be represented as UTF-8 JSON', previous: $error);
        }
    }

    /**
     * Scan a syntactically validated JSON value and track object keys independently at each depth.
     */
    private static function scan(string $json, int &$offset): void
    {
        self::whitespace($json, $offset);
        $token = $json[$offset];

        if ($token === '"') {
            self::stringToken($json, $offset);

            return;
        }

        if ($token === '{' || $token === '[') {
            $object = $token === '{';
            $closing = $object ? '}' : ']';
            ++$offset;
            self::whitespace($json, $offset);
            $seen = [];

            while ($json[$offset] !== $closing) {
                if ($object) {
                    $key = self::stringToken($json, $offset);
                    $identity = ':' . $key;

                    if (isset($seen[$identity])) {
                        throw new ProtocolViolation('Duplicate JSON object member');
                    }

                    $seen[$identity] = true;
                    self::whitespace($json, $offset);
                    ++$offset;
                }

                self::scan($json, $offset);
                self::whitespace($json, $offset);

                if ($json[$offset] !== ',') {
                    break;
                }

                ++$offset;
                self::whitespace($json, $offset);
            }

            ++$offset;

            return;
        }

        $length = strlen($json);

        while ($offset < $length && !str_contains(",]} \t\r\n", $json[$offset])) {
            ++$offset;
        }
    }

    /**
     * Decode a validated string token so escaped and literal spellings compare identically.
     */
    private static function stringToken(string $json, int &$offset): string
    {
        $start = $offset++;

        while ($json[$offset] !== '"') {
            if ($json[$offset] === '\\') {
                ++$offset;
            }

            ++$offset;
        }

        ++$offset;
        $value = json_decode(substr($json, $start, $offset - $start), flags: JSON_THROW_ON_ERROR);

        if (!is_string($value)) {
            throw new ProtocolViolation('Expected JSON string token');
        }

        return $value;
    }

    /**
     * Advance over only the four whitespace characters admitted by JSON.
     */
    private static function whitespace(string $json, int &$offset): void
    {
        $length = strlen($json);

        while ($offset < $length && str_contains(" \t\r\n", $json[$offset])) {
            ++$offset;
        }
    }
}
