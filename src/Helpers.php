<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Contract\IoHost\CredentialBinding;
use Stashd\PluginSdk\Contract\IoHost\CredentialReference;
use Stashd\PluginSdk\Helper\Credential;
use Stashd\PluginSdk\Helper\Process;
use Stashd\PluginSdk\Helper\Stream;
use Stashd\PluginSdk\Helper\Writer;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\Resource\HelperProcessCall;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagingArea;

/**
 * Run helper tools and create temporary output for the current plugin call.
 */
final class Helpers
{
    /**
     * The current plugin call.
     */
    private readonly Invocation $invocation;

    /**
     * Credentials the host made available to this call. These are selectors, not secret values.
     * @var list<Credential>
     */
    private readonly array $credentials;

    /**
     * Create the helper API for one plugin call.
     * @param list<Credential> $credentials
     */
    public function __construct(Invocation $invocation, array $credentials = [])
    {
        $this->invocation = $invocation;
        $this->credentials = $credentials;
    }

    /**
     * Return the credentials this plugin call may pass to helpers.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->credentials;
    }

    /**
     * Start a helper tool. Arguments are passed directly, without a shell.
     * Pass only credentials returned by credentials(); the host supplies their secret values.
     * @param list<string> $args
     * @param list<Credential> $credentials
     */
    public function start(string $name, array $args = [], ?Stream $input = null, ?Writer $output = null, array $credentials = []): Process
    {
        $bindings = [];

        foreach ($credentials as $credential) {
            if (!in_array($credential, $this->credentials, true)) {
                throw new \InvalidArgumentException('Helper credential was not supplied to this plugin call');
            }

            $bindings[] = new CredentialBinding($credential->name, new CredentialReference($credential->reference));
        }

        return new Process(HelperProcessCall::start($this->invocation, $name, $args, $input?->transfer(), $output?->transfer(), $bindings));
    }

    /**
     * Create temporary output that PHP can write to or hand to a helper.
     */
    public function stage(?string $mediaType = null): Writer
    {
        $area = $this->invocation->typedCall('stashd:plugin/io-host.open-staging-area', [], [], ['kind' => 'named', 'name' => 'staging-area'], 'io-host');

        if (!$area instanceof RemoteStagingArea) {
            $this->invocation->violate('Host did not return a staging area');
        }

        try {
            return new Writer($area->create($mediaType));
        } finally {
            $area->close();
        }
    }
}
