<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime;

use Closure;
use Stashd\PluginSdk\ReadableResource;

final class RuntimeReadableResource implements ReadableResource
{
    private bool $eof = false;

    /** @param Closure $call */
    public function __construct(private Closure $call, private string $reference) {}

    public function read(int $maximumBytes = 65536): string
    {
        if ($this->eof) {
            return '';
        }
        $result = ($this->call)('resource.read', ['reference' => $this->reference, 'maximum_bytes' => $maximumBytes]);

        if (! is_array($result)) {
            return '';
        }
        $encoded = is_string($result['data'] ?? null) ? $result['data'] : '';
        $data = base64_decode($encoded, true);
        $this->eof = (bool) ($result['eof'] ?? false);

        return $data === false ? '' : $data;
    }

    public function isEof(): bool
    {
        return $this->eof;
    }

    public function close(): void
    {
        $this->eof = true;
    }
}
