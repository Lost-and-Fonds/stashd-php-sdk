<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * A byte stream available during the current call.
 * Close it when finished; it cannot be used after the call ends.
 */
interface ByteStream
{
    /**
     * Release this resource; closing it again or using it afterwards is an error.
     */
    public function close(): void;

    /**
     * Run read on this byte stream.
     * Ordinary host failures are distinct from protocol violations.
     * @return list<int>|null
     */
    public function read(): ?array;

}
