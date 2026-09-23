<?php

declare(strict_types=1);

namespace Stashd\PluginSdk;

/**
 * Invocation-scoped capabilities. pluginDataPath is a persistent, private
 * writable directory mounted at /plugin-data; package and staging files are separate.
 */
final readonly class PluginContext
{
    public function __construct(
        public Logger $logger = new NullLogger(),
        public ProgressReporter $progress = new NullProgressReporter(),
        public HttpClient $http = new UnavailableHttpClient(),
        public ?StagingArea $staging = null,
        public ?HelperRunner $helpers = null,
        public string $pluginDataPath = '/plugin-data',
    ) {}
}
