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
 * Start approved helpers and stage their output during a plugin call.
 */
final class Helpers
{
    /**
     * Invocation supplying these host-managed operations.
     */
    private readonly Invocation $invocation;

    /**
     * Selectors supplied by the host for this plugin call, never raw secret values.
     * @var list<Credential>
     */
    private readonly array $credentials;

    /**
     * Receive only the capabilities and credential selectors granted to this plugin call.
     * @param list<Credential> $credentials
     */
    public function __construct(Invocation $invocation, array $credentials = [])
    {
        $this->invocation = $invocation;
        $this->credentials = $credentials;
    }

    /**
     * Get the selectors granted by the host for this plugin call.
     * @return list<Credential>
     */
    public function credentials(): array
    {
        return $this->credentials;
    }

    /**
     * Start an approved helper; arguments are passed unchanged, not through a shell.
     * Supply only credential selectors granted for this call; the host resolves their values.
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
     * Create unfinished output that a helper may fill before returning it on normal exit.
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
