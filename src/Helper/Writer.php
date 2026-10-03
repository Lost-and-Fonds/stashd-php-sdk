<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;

/**
 * Unfinished staged output; transferring it to a helper suspends use until normal return.
 */
final class Writer
{
    /**
     * Keep the invocation-bound writer private to prevent raw handle access.
     */
    private readonly RemoteStagedWriter $writer;

    /**
     * Wrap a live writer returned by the host or by a finished helper.
     */
    public function __construct(RemoteStagedWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Supply the writer to the helper boundary, which checks ownership before transfer.
     */
    public function transfer(): RemoteStagedWriter
    {
        return $this->writer;
    }

    /**
     * Append raw bytes before the writer is transferred to a helper.
     */
    public function write(string $bytes): void
    {
        $this->writer->write(array_values(unpack('C*', $bytes)));
    }

    /**
     * Finish returned output once and receive a descriptor that can be reopened.
     */
    public function finish(): Artifact
    {
        return new Artifact($this->writer->finishArtifact());
    }

    /**
     * Discard unfinished output; transferred writers cannot be discarded by the plugin.
     */
    public function close(): void
    {
        $this->writer->close();
    }
}
