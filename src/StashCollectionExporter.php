<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

interface StashCollectionExporter
{
    public function key(): string;

    public function label(): string;

    public function export(StashCollection $collection, PluginContext $context): ExportedFile;
}
