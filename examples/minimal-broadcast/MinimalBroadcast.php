<?php

declare(strict_types=1);

namespace ExamplePlugin;

use Stashd\PluginSdk as Sdk;

final class MinimalBroadcast implements Sdk\BroadcastPlugin
{
    public function prepare(Sdk\PublishRequest $request, Sdk\PluginContext $context): Sdk\Preparation
    {
        return new Sdk\Preparation();
    }

    public function publish(Sdk\PublishRequest $request, Sdk\PluginContext $context): Sdk\Publication
    {
        return new Sdk\Publication(new Sdk\Artifact(''));
    }

    public function finalize(Sdk\FinalizationRequest $request, Sdk\PluginContext $context): Sdk\Publication
    {
        return $request->publication;
    }

    public function operation(Sdk\OperationRequest $request, Sdk\PluginContext $context): Sdk\OperationResult
    {
        return new Sdk\OperationResult();
    }
}
