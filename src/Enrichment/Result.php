<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Enrichment;

use Stashd\PluginSdk\Shared\Metadata;

/**
 * Metadata and durable derived files returned by enrichment.
 */
final readonly class Result
{
    /**
     * Return plugin-owned metadata and zero or more derived files.
     * @param list<Metadata> $metadata Metadata to attach to the saved item.
     * @param list<DerivedAsset> $assets Finished files with source and activity details.
     */
    public function __construct(public array $metadata = [], public array $assets = []) {}
}
