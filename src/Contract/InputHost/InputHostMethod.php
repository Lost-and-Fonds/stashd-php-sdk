<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\InputHost;

/**
 * RPC operations declared by the input-host interface.
 */
enum InputHostMethod: string
{
    /**
     * Identifies the open credential wire operation.
     */
    case OpenCredential = 'stashd:plugin/input-host.open-credential';
    /**
     * Identifies the commit discovery batch wire operation.
     */
    case CommitDiscoveryBatch = 'stashd:plugin/input-host.commit-discovery-batch';
    /**
     * Identifies the credential access.read wire operation.
     */
    case CredentialAccessRead = 'stashd:plugin/input-host.credential-access.read';
}
