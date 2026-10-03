<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Runtime\Resource\RemoteStagedArtifact;

/**
 * Finished staged output that can be read again during this plugin call.
 */
final class Artifact
{
    /**
     * Keep the finished output tied to its original call.
     */
    private readonly RemoteStagedArtifact $artifact;

    /**
     * Wrap the host-issued finished output without exposing its receipt.
     */
    public function __construct(RemoteStagedArtifact $artifact)
    {
        $this->artifact = $artifact;
    }

    /**
     * Read the finished bytes lazily and close the reader when iteration ends.
     * @return \Generator<int, string>
     */
    public function chunks(): \Generator
    {
        $stream = $this->artifact->open();

        try {
            while (($chunk = $stream->read()) !== null) {
                yield pack('C*', ...$chunk);
            }
        } finally {
            $stream->close();
        }
    }
}
