<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final class OptionValue
{
    private function __construct(public readonly string $kind, public readonly bool|int|string $value) {}

    public static function boolean(bool $value): self
    {
        return new self('boolean', $value);
    }

    public static function number(int $value): self
    {
        return new self('number', $value);
    }

    public static function text(string $value): self
    {
        return new self('text', $value);
    }

    /** @param array{tag?: mixed, value?: mixed} $value */
    public static function fromWire(array $value): self
    {
        $tag = $value['tag'] ?? null;

        if (! is_string($tag) || ! array_key_exists('value', $value)) {
            throw new InvalidPluginResultException('option value is malformed');
        }
        $raw = $value['value'];

        return match ($tag) {
            'boolean' => is_bool($raw) ? self::boolean($raw) : throw new InvalidPluginResultException('boolean option value is malformed'),
            'number' => is_int($raw) ? self::number($raw) : throw new InvalidPluginResultException('number option value is malformed'),
            'text' => is_string($raw) ? self::text($raw) : throw new InvalidPluginResultException('text option value is malformed'),
            default => throw new InvalidPluginResultException('unknown option value type'),
        };
    }

    /** @return array{tag: string, value: bool|int|string} */
    public function toWire(): array
    {
        return ['tag' => $this->kind, 'value' => $this->value];
    }
}
