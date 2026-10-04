<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use LogicException;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;

/**
 * Temporary output that can be written now or handed to a helper.
 */
final class Writer
{
    /**
     * The staging writer, or null after handing it to a helper.
     */
    private ?RemoteStagedWriter $writer;

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
        $writer = $this->writer ?? throw new LogicException('Writer was already handed to a helper');
        $this->writer = null;

        return $writer;
    }

    /**
     * Write bytes to the staged output.
     */
    public function write(string $bytes): void
    {
        $values = [];

        for ($index = 0, $length = strlen($bytes); $index < $length; ++$index) {
            $values[] = ord($bytes[$index]);
        }

        ($this->writer ?? throw new LogicException('Writer was already handed to a helper'))->write($values);
    }

    /**
     * Finish the output and make it readable as an Artifact.
     */
    public function finish(): Artifact
    {
        return new Artifact(($this->writer ?? throw new LogicException('Writer was already handed to a helper'))->finishArtifact());
    }

    /**
     * Discard unfinished output. A writer already handed to a helper cannot be closed here.
     */
    public function close(): void
    {
        ($this->writer ?? throw new LogicException('Writer was already handed to a helper'))->close();
    }
}
