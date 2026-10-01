<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Shared;

use Stashd\PluginSdk\Runtime\Codec\Json;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Structurally validated plugin-owned facet; schema semantics and secret exclusion belong to its producer.
 */
final readonly class Metadata
{
    /**
     * Opaque nonempty producer-owned schema identity, preserved without normalization.
     */
    public string $schema;

    /**
     * Exact original object-root JSON text, including whitespace and numeric spelling.
     */
    public string $json;

    /**
     * Validate only shared structure, never infer domain meaning from field names or schema syntax.
     */
    public function __construct(string $schema, string $json)
    {
        if ($schema === '' || preg_match('//u', $schema) !== 1 || !Json::decode($json) instanceof stdClass) {
            throw new ProtocolViolation('Metadata requires a nonempty Unicode schema and duplicate-free JSON object');
        }

        $this->schema = $schema;
        $this->json = $json;
    }
}
