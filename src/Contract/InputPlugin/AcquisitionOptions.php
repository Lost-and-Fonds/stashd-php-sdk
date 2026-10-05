<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Settings and credentials for saving an item.
 */
final readonly class AcquisitionOptions
{
    /**
     * Create the acquisition options.
     *
     * @param list<InputOption> $options Settings selected for this work.
     * @param list<CredentialBinding> $credentials Named credentials available during this call.
     */
    public function __construct(
        public array $options,
        public array $credentials,
    ) {}
}
