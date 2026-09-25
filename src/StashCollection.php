<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class StashCollection
{
    /**
     * @param list<StashCollectionEntry> $entries
     * @param list<Setting> $options Exporter options supplied with this export invocation
     */
    public function __construct(
        public array $entries = [],
        public ?string $reference = null,
        public ?string $title = null,
        public array $options = [],
    ) {}
}
