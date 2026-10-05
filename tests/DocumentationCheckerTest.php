<?php

declare(strict_types=1);

use Stashd\PluginSdk\Tooling\DocumentationChecker;

it('requires useful description presence on every declaration kind', function (string $declaration, int $missing): void {
    expect((new DocumentationChecker())->check('<?php ' . $declaration, 'fixture'))->toHaveCount($missing);
})->with([
    ['/** Describes an immutable contract fact. */ class Value {}', 0],
    ['class Value {}', 1],
    ['/** Author lifecycle contract. */ interface Lifecycle {}', 0],
    ['interface Lifecycle {}', 1],
    ['enum State { case Open; }', 2],
    ['/** Tracks supported states. */ enum State { case Open; }', 1],
    ['/** Tracks supported states. */ enum State { /** Marks the open state. */ case Open; }', 0],
    ['/** Holds protocol constants. */ class Values { public const LIMIT = 1; }', 1],
    ['/** Holds protocol constants. */ class Values { /** Maximum allowed item count. */ public const LIMIT = 1; }', 0],
    ['trait Shared {}', 1],
    ['/** Resolves an opaque reference. */ function resolve() {}', 0],
    ['function resolve() {}', 1],
    ['/** Contract value. */ class Value { /** Creates a validated value. */ public function __construct() {} }', 0],
    ['/** Contract value. */ class Value { public function __construct() {} }', 1],
    ['/** Contract value. */ class Value { private function validate() {} }', 1],
    ['/** Contract value. */ class Value { /** Checks invariants before use. */ private function validate() {} }', 0],
    ['/** Contract value. */ class Value { private string $reference; }', 1],
    ['/** Contract value. */ class Value { /** Opaque reference retained without normalization. */ private string $reference; }', 0],
    ['/** Contract value. */ class Value { /** Creates a value. */ public function __construct(/** Opaque reference retained verbatim. */ public string $reference) {} }', 0],
    ['/** Contract value. */ class Value { /** Creates a value. */ public function __construct(public string $reference) {} }', 1],
    ['/** @internal */ class Value {}', 1],
    ['/** */ class Value {}', 1],
    ["/** \n * \t \n */ class Value {}", 1],
    ["/** Opaque reference wrapper.\n * @internal\n */ class Value {}", 0],
    ["/** @template T\n * continuation of an annotation\n */ class Value {}", 1],
    ['$value = new class {};', 1],
]);

it('checks promoted properties against their own constructor parameter descriptions', function (string $tags, string $parameters, int $missing): void {
    $source = '<?php
/**
 * A saved asset that a plugin may read.
 */
final readonly class Asset
{
    /**
     * Create a saved asset.
     *
' . $tags . '
     */
    public function __construct(' . $parameters . ') {}
}';

    expect((new DocumentationChecker())->check($source, 'fixture'))->toHaveCount($missing);
})->with([
    'documented' => ['     * @param string $id Stable asset ID.', 'public string $id', 0],
    'missing tag' => ['', 'public string $id', 1],
    'wrong name' => ['     * @param string $other Stable asset ID.', 'public string $id', 1],
    'empty description' => ['     * @param string $id', 'public string $id', 1],
    'next annotation is not prose' => ["     * @param string \$id\n     * @return void", 'public string $id', 1],
    'punctuation' => ['     * @param string $id ...', 'public string $id', 1],
    'placeholder' => ['     * @param string $id TODO', 'public string $id', 1],
    'repeated name' => ['     * @param string $id ID.', 'public string $id', 1],
    'repeated type' => ['     * @param string $id string', 'public string $id', 1],
    'multiple properties' => ["     * @param string \$id Stable asset ID.\n     * @param string|null \$reference Opaque reference used to read the asset.", 'public string $id, public ?string $reference', 0],
    'partially documented' => ['     * @param string $id Stable asset ID.', 'public string $id, public string $reference', 1],
    'generic with spaces' => ['     * @param array<string, string> $metadata Metadata grouped by schema.', 'public array $metadata', 0],
    'ordinary parameter' => ['', 'string $id', 0],
]);

it('still requires constructor prose and separate documentation for ordinary properties', function (): void {
    $source = <<<'PHP'
        <?php
        /**
         * A saved asset that a plugin may read.
         */
        final readonly class Asset
        {
            public string $reference;

            /**
             * @param string $id Stable asset ID.
             * @param string $reference Opaque reference used to read the asset.
             */
            public function __construct(public string $id, string $reference)
            {
                $this->reference = $reference;
            }
        }
        PHP;

    expect((new DocumentationChecker())->check($source, 'fixture'))->toHaveCount(2);
});
