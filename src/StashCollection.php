<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class StashCollection
{
    /** @param list<StashCollectionEntry> $entries */
    public function __construct(public array $entries = []) {}
}
