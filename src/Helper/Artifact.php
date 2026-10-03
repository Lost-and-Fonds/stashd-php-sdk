<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Runtime\Resource\RemoteStagedArtifact;

/**
 * Finished temporary output from a helper or plugin.
 */
final class Artifact
{
    /**
     * The host-managed finished output.
     */
    private readonly RemoteStagedArtifact $artifact;

    /**
     * Create a readable artifact from finished staged output.
     */
    public function __construct(RemoteStagedArtifact $artifact)
    {
        $this->artifact = $artifact;
    }

    /**
     * Read the artifact a chunk at a time. The stream closes automatically when iteration ends.
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
