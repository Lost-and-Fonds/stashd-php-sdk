<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

/**
 * A credential that this plugin call may pass to a helper.
 */
final readonly class Credential
{
    /**
     * Create a helper credential selector.
     * @param string $name Environment variable name the helper receives.
     * @param string $reference Opaque selector reference, not the secret itself.
     */
    public function __construct(public string $name, public string $reference) {}
}
