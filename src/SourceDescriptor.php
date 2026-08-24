<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

/** Source-identification values, distinct from per-Input ingestion options. */
final readonly class SourceDescriptor
{
    /** @param array<string, OptionValue> $values */
    public function __construct(public array $values) {}

    public function text(string $key): ?string
    {
        $value = $this->values[$key] ?? null;

        return $value?->kind === 'text' ? (string) $value->value : null;
    }
}
