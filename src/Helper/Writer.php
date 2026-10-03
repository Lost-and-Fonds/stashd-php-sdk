<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;

/**
 * Temporary output that can be written now or handed to a helper.
 */
final class Writer
{
    /**
     * The host-managed staging writer.
     */
    private readonly RemoteStagedWriter $writer;

    /**
     * Create writable staged output.
     */
    public function __construct(RemoteStagedWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Return the underlying writer for one-time transfer to a helper.
     */
    public function transfer(): RemoteStagedWriter
    {
        return $this->writer;
    }

    /**
     * Write bytes to the staged output.
     */
    public function write(string $bytes): void
    {
        $this->writer->write(array_values(unpack('C*', $bytes)));
    }

    /**
     * Finish the output and make it readable as an Artifact.
     */
    public function finish(): Artifact
    {
        return new Artifact($this->writer->finishArtifact());
    }

    /**
     * Discard unfinished output. A writer already handed to a helper cannot be closed here.
     */
    public function close(): void
    {
        $this->writer->close();
    }
}
