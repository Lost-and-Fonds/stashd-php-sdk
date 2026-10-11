<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

use Stashd\PluginSdk\Input\Acquisition;
use Stashd\PluginSdk\Input\AcquisitionResult;
use Stashd\PluginSdk\Input\Discovery;
use Stashd\PluginSdk\Input\Resolve;
use Stashd\PluginSdk\Input\ResolvedInput;
use Stashd\PluginSdk\Input\Source;

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
     * Check whether this source is understood without host calls or credentials.
     */
    public function canResolve(Source $source): bool;

    /**
     * Find items and save batches with a restart point, then save the final result.
     */
    public function discover(Discovery $discovery): void;

    /**
     * Save one item and return its finished files and any missing work.
     */
    public function acquire(Acquisition $acquisition): AcquisitionResult;
}
