<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Plugin-defined JSON metadata. The producer defines its meaning and must exclude secrets.
 */
final readonly class Metadata
{
    /**
     * Create metadata from a non-empty schema name and a JSON object.
     */
    public function __construct(
        /**
         * Non-empty schema identifier chosen by the producer.
         */
        public string $schema,
        /**
         * JSON object text with no duplicate member names; kept as supplied.
         */
        public string $json,
    ) {
        if ($schema === '' || preg_match('//u', $schema) !== 1 || !Json::decode($json) instanceof stdClass) {
            throw new ProtocolViolation('Metadata requires a nonempty Unicode schema and duplicate-free JSON object');
        }

    }
}
