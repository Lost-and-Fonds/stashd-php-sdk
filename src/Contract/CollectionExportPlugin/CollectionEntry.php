<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\CollectionExportPlugin;

/**
 * Immutable collection-export-plugin.collection-entry contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class CollectionEntry
{
    /**
     * Canonical reference value; retained in contract order without normalization.
     * @var string
     */
    public string $reference;

    /**
     * Canonical title value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $title;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string $reference
     * @param string|null $title
     */
    public function __construct(
        string $reference,
        ?string $title,
    ) {
        $this->reference = $reference;
        $this->title = $title;
    }
}
