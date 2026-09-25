<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use RuntimeException;
use Stashd\PluginSdk\CapabilityUnavailableException;
use Stashd\PluginSdk\StagedArtifact;
use Stashd\PluginSdk\StagingArea;

final readonly class RuntimeStagingArea implements StagingArea
{
    /** @param Closure $call */
    public function __construct(private Closure $call, private bool $allowWrite = true) {}

    public function write(string $relativePath, string $content, ?string $mediaType = null): StagedArtifact
    {
        if (! $this->allowWrite) {
            throw new CapabilityUnavailableException('staging.write is not available in this invocation.');
        }

        $result = ($this->call)('staging.write', [
            'relative-path' => $relativePath,
            'content' => array_values(unpack('C*', $content) ?: []),
            'media-type' => $mediaType,
        ]);

        return $this->artifact(RuntimeFrameCodec::object($result));
    }

    public function stage(string $relativePath, ?string $mediaType = null): StagedArtifact
    {
        $result = ($this->call)('staging.stage', [
            'relative-path' => $relativePath,
            'media-type' => $mediaType,
        ]);

        return $this->artifact(RuntimeFrameCodec::object($result));
    }

    /** @param array<string, mixed> $result */
    private function artifact(array $result): StagedArtifact
    {
        if (! is_string($result['reference'] ?? null)
            || ! array_key_exists('media-type', $result)
            || (! is_string($result['media-type']) && $result['media-type'] !== null)
            || ! is_int($result['size-bytes'] ?? null)
            || $result['size-bytes'] < 0
            || (! $this->allowWrite && ! array_key_exists('role', $result))) {
            throw new RuntimeException('Plugin staging returned an invalid artifact.');
        }

        $role = $result['role'] ?? null;

        if ($role !== null && (! is_string($role) || ! in_array($role, ['primary', 'captions', 'artwork', 'metadata'], true))) {
            throw new RuntimeException('Plugin staging returned an invalid role.');
        }

        return new StagedArtifact(
            $result['reference'],
            $result['media-type'],
            $result['size-bytes'],
            is_string($role) ? $role : null,
        );
    }
}
