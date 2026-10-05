<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Broadcast;

use Stashd\PluginSdk\Helper\Artifact;

/**
 * Completed publication, with optional local output and file-report status.
 */
final readonly class Publication
{
    /**
     * Describe the publication result without adding a finalize phase.
     * @param Artifact|null $artifact The finished local output, or null for remote-only publication.
     * @param bool $filesComplete Whether all applicable destination files were reported.
     */
    public function __construct(public ?Artifact $artifact = null, public bool $filesComplete = false) {}

    /**
     * Return finished local output and the reporting status.
     */
    public static function local(Artifact $artifact, bool $filesComplete = false): self
    {
        return new self($artifact, $filesComplete);
    }
}
