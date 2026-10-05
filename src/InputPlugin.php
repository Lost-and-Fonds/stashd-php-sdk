<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Input\Acquisition;
use Stashd\PluginSdk\Input\AcquisitionResult;
use Stashd\PluginSdk\Input\Discovery;
use Stashd\PluginSdk\Input\Resolve;
use Stashd\PluginSdk\Input\ResolveDelegation;
use Stashd\PluginSdk\Input\ResolvedInput;

/**
 * Identify sources, discover their items in batches, and save selected items.
 */
interface InputPlugin
{
    /**
     * Identify a caller-supplied source for later discovery.
     */
    public function resolve(Resolve $request): ResolvedInput;

    /**
     * Identify a reference handed off by another Input plugin.
     */
    public function resolveDelegation(ResolveDelegation $request): ResolvedInput;

    /**
     * Save bounded batches and acknowledge the final coverage outcome.
     */
    public function discover(Discovery $discovery): void;

    /**
     * Save one item independently, returning its finished files and coverage.
     */
    public function acquire(Acquisition $acquisition): AcquisitionResult;
}
