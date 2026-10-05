<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * An opaque reference for another Input plugin to resolve.
 */
final readonly class InputDelegation
{
    /**
     * Create the input delegation.
     *
     * @param string $reference Opaque reference interpreted by the service that issued it.
     */
    public function __construct(
        public string $reference,
    ) {}
}
