<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use RuntimeException;

final class RuntimeFrameCodec
{
    private const MAX_FRAME_BYTES = 268_435_456;

    /** @return array<string, mixed> */
    public static function object(mixed $value): array
    {
        $result = [];

        foreach (is_array($value) ? $value : [] as $key => $entry) {
            if (is_string($key)) {
                $result[$key] = $entry;
            }
        }

        return $result;
    }

    /**
     * @param resource $stream
     * @param array<string, mixed> $message
     */
    public static function write($stream, array $message): void
    {
        $payload = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $frame = pack('N', strlen($payload)) . $payload;

        self::writeAll($stream, $frame);

        fflush($stream);
    }

    /**
     * @param resource $stream
     * @return array<int|string, mixed>|null
     */
    public static function read($stream, float $timeout = 30.0): ?array
    {
        $deadline = microtime(true) + $timeout;
        $header = self::readBytes($stream, 4, $deadline);

        if ($header === '') {
            return null;
        }

        if (strlen($header) !== 4) {
            throw new RuntimeException('plugin IPC frame header is truncated');
        }
        $unpacked = unpack('Nlength', $header);
        $length = $unpacked['length'] ?? null;

        if (! is_int($length) || $length < 2 || $length > self::MAX_FRAME_BYTES) {
            throw new RuntimeException('plugin IPC frame is outside the size limit');
        }
        $payload = self::readBytes($stream, $length, $deadline);

        if (strlen($payload) !== $length) {
            throw new RuntimeException('plugin IPC frame is truncated');
        }
        $message = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($message)) {
            throw new RuntimeException('plugin IPC message is not an object');
        }

        $result = [];

        foreach ($message as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @param resource $stream */
    private static function readBytes($stream, int $length, float $deadline): string
    {
        $result = '';

        while (strlen($result) < $length) {
            $remaining = $deadline - microtime(true);

            if ($remaining <= 0) {
                throw new RuntimeException('plugin IPC read timed out');
            }
            /** @var array<int, resource> $read */
            $read = [$stream];
            /** @var array<int, resource> $write */
            $write = [];
            /** @var array<int, resource> $except */
            $except = [];
            $seconds = (int) $remaining;
            $microseconds = (int) (($remaining - $seconds) * 1_000_000);

            if (stream_select($read, $write, $except, $seconds, $microseconds) === 0) {
                throw new RuntimeException('plugin IPC read timed out');
            }
            $chunkLength = max(1, min(65_536, $length - strlen($result)));
            $chunk = fread($stream, $chunkLength);

            if ($chunk === false || $chunk === '') {
                return $result;
            }
            $result .= $chunk;
        }

        return $result;
    }

    /** @param resource $stream */
    private static function writeAll($stream, string $data): void
    {
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $written = fwrite($stream, substr($data, $offset, 65_536));

            if (! is_int($written) || $written <= 0) {
                throw new RuntimeException('plugin IPC write failed');
            }

            $offset += $written;
        }
    }
}
