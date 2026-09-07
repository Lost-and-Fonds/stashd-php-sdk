<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

interface BroadcastPlugin
{
    public function prepare(PublishRequest $request, PluginContext $context): Preparation;

    public function publish(PublishRequest $request, PluginContext $context): Publication;

    public function finalize(FinalizationRequest $request, PluginContext $context): Publication;

    public function operation(OperationRequest $request, PluginContext $context): OperationResult;
}
