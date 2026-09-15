<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

final readonly class ExportedFile
{
    public function __construct(
        public string $filename,
        public string $contentType,
        public string $contents,
    ) {}
}
