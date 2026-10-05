<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Contract\IoHost;

/**
 * Plugin-defined JSON metadata identified by a schema.
 */
final readonly class PluginMetadata
{
    /**
     * Create the plugin metadata.
     *
     * @param string $schema Non-empty schema identifier chosen by the metadata producer.
     * @param string $json JSON object text with no duplicate member names; must not contain secrets.
     */
    public function __construct(
        public string $schema,
        public string $json,
    ) {}
}
