<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Generator;
use Stashd\PluginSdk\Contract\IoHost\StagedArtifact;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedArtifact;

/**
 * Finished temporary output from a helper or plugin.
 */
final class Artifact
{
    /**
     * Create a readable artifact from completed output.
     *
     * @param RemoteStagedArtifact $artifact Completed output available during this call.
     */
    public function __construct(
        private readonly RemoteStagedArtifact $artifact,
    ) {}

    /**
     * Return the finished output for a successful plugin result.
     */
    public function receipt(): StagedArtifact
    {
        return $this->artifact->receipt();
    }

    /**
     * Read the artifact a chunk at a time. The stream closes automatically when iteration ends.
     * @return Generator<int, string>
     */
    public function chunks(): Generator
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
