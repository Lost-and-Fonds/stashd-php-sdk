<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

/**
 * A file published for a saved asset.
 */
final readonly class PublishedFile
{
    /**
     * Create the published file.
     *
     * @param string|null $itemId Identifier of the item this result belongs to.
     * @param string|null $assetId Identifier of the saved asset this file represents.
     * @param string $relativePath Published path relative to the destination.
     */
    public function __construct(
        public ?string $itemId,
        public ?string $assetId,
        public string $relativePath,
    ) {}
}
