<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Input;

/**
 * An opaque source reference with optional plugin-defined settings.
 */
final readonly class Source
{
    /**
     * Keep caller-supplied values in their original order.
     * @param string|null $reference Opaque reference understood by the Input.
     * @param list<Option> $values Values supplied by the caller; keys can repeat.
     */
    public function __construct(public ?string $reference = null, public array $values = []) {}

    /**
     * Return the first text value for a key, or null when absent.
     */
    public function text(string $key): ?string
    {
        foreach ($this->values as $entry) {
            if ($entry->key === $key && is_string($entry->value)) {
                return $entry->value;
            }
        }

        return null;
    }
}
