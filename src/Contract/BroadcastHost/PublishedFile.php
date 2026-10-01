<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\BroadcastHost;

/**
 * Immutable broadcast-host.published-file contract fact.
 * Field order and opaque values follow stashd:plugin@0.17.0 without normalization.
 */
final readonly class PublishedFile
{
    /**
     * Canonical item-id value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $itemId;

    /**
     * Canonical asset-id value; retained in contract order without normalization.
     * @var string|null
     */
    public ?string $assetId;

    /**
     * Canonical relative-path value; retained in contract order without normalization.
     * @var string
     */
    public string $relativePath;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param string|null $itemId
     * @param string|null $assetId
     * @param string $relativePath
     */
    public function __construct(
        ?string $itemId,
        ?string $assetId,
        string $relativePath,
    ) {
        $this->itemId = $itemId;
        $this->assetId = $assetId;
        $this->relativePath = $relativePath;
    }
}
