<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * An opaque reference and plugin-defined values understood by an Input.
 */
final readonly class Source
{
    /**
     * Create the source.
     *
     * @param string|null $reference Opaque reference interpreted by the service that issued it.
     * @param list<SourceValue> $values Named values returned by the operation.
     */
    public function __construct(
        public ?string $reference,
        public array $values,
    ) {}
}
