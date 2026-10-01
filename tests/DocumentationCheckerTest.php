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
    ['enum State { case Open; }', 1],
    ['trait Shared {}', 1],
    ['/** Resolves an opaque reference. */ function resolve() {}', 0],
    ['function resolve() {}', 1],
    ['/** Contract value. */ class Value { /** Creates a validated value. */ public function __construct() {} }', 0],
    ['/** Contract value. */ class Value { public function __construct() {} }', 1],
    ['/** Contract value. */ class Value { private function validate() {} }', 1],
    ['/** Contract value. */ class Value { /** Checks invariants before use. */ private function validate() {} }', 0],
    ['/** Contract value. */ class Value { private string $reference; }', 1],
    ['/** Contract value. */ class Value { /** Opaque reference retained without normalization. */ private string $reference; }', 0],
    ['/** Contract value. */ class Value { /** Creates a value. */ public function __construct(public string $reference) {} }', 1],
    ['/** Contract value. */ class Value { /** Creates a value. */ public function __construct(/** Opaque reference retained verbatim. */ public string $reference) {} }', 0],
    ['/** @internal */ class Value {}', 1],
    ['/** */ class Value {}', 1],
    ["/** \n * \t \n */ class Value {}", 1],
    ["/** Opaque reference wrapper.\n * @internal\n */ class Value {}", 0],
    ["/** @template T\n * continuation of an annotation\n */ class Value {}", 1],
    ['$value = new class {};', 1],
]);
