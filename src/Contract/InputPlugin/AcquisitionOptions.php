<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputPlugin;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;

/**
 * Immutable input-plugin.acquisition-options contract fact.
 * Field order and opaque values follow stashd:plugin@0.18.0 without normalization.
 */
final readonly class AcquisitionOptions
{
    /**
     * Canonical options value; retained in contract order without normalization.
     * @var list<InputOption>
     */
    public array $options;

    /**
     * Canonical credentials value; retained in contract order without normalization.
     * @var list<CredentialBinding>
     */
    public array $credentials;

    /**
     * Assemble the complete contract fact; wire and lifecycle validators enforce boundary invariants.
     * @param list<InputOption> $options
     * @param list<CredentialBinding> $credentials
     */
    public function __construct(
        array $options,
        array $credentials,
    ) {
        $this->options = $options;
        $this->credentials = $credentials;
    }
}
