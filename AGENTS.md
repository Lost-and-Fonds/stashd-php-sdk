# Stashd PHP SDK

This repository is the PHP SDK for Stashd plugins.

Stashd plugins communicate with Core through the frozen `stashd:plugin@0.18.0` contract. The SDK's job is to make that contract feel like normal, pleasant PHP.

The public SDK should hide protocol machinery wherever possible.

## Priorities

In order:

1. Correctly implement the frozen plugin contract.
2. Give plugin authors a small, obvious PHP API.
3. Keep protocol/runtime complexity below the public API boundary.
4. Prefer simple code over abstraction.
5. Prefer deletion over compatibility with unfinished rewrite APIs.

This is an unreleased rewrite. Do not preserve bad APIs merely because they already exist.

## PHP version

Target PHP 8.5.

Use modern PHP features when they make the code simpler.

The SDK requires 64-bit PHP.

Prefer:

- constructor property promotion
- `readonly`
- native enums
- union and nullable types
- named arguments where useful
- generators for streaming values
- `#[NoDiscard]` where ignoring a result is probably a mistake

Import attributes and classes normally. Do not write things like `#[\NoDiscard]`.

## Public API

The important public entry points should be obvious when a developer opens `src/`:

- `Stashd\PluginSdk\InputPlugin`
- `Stashd\PluginSdk\BroadcastPlugin`
- `Stashd\PluginSdk\EnrichmentPlugin`
- `Stashd\PluginSdk\CollectionExporter`
- `Stashd\PluginSdk\Helpers`

Supporting author-facing types should live in small domain namespaces such as:

- `Input\`
- `Broadcast\`
- `Enrichment\`
- `CollectionExport\`
- `Helper\`

Do not make plugin authors use types from:

- `Contract\`
- `Runtime\`
- generated codecs
- RPC internals

Those are implementation details.

## Design from the plugin author's point of view

Public names should describe what the developer thinks they are doing.

Prefer names such as:

- `resolve`
- `discover`
- `acquire`
- `publish`
- `action`
- `enrich`
- `export`
- `start`
- `events`
- `cancel`

Do not blindly copy vague protocol names into the public API.

Be suspicious of names such as:

- `Operation`
- `Context`
- `Descriptor`
- `Value`
- `Request`
- `Result`

They are allowed when they genuinely make the API clearer, but do not create them automatically.

## Keep the SDK boring

Use normal PHP types whenever they are sufficient:

- `int`
- `string`
- `bool`
- `?T`
- native enums
- `list<T>` arrays

Do not create a value object merely because a value has a name.

Create a class when it represents a useful domain concept, carries behaviour, enforces an important invariant, or materially improves the author API.

Do not recreate WIT's type hierarchy in PHP.

## Integers

Protocol integer widths belong primarily to the runtime boundary.

Author-facing byte counts, offsets, lengths, counters, and similar realistic Stashd quantities should normally use PHP `int`.

The protocol/runtime must still validate and preserve the complete WIT value range.

If a valid protocol value cannot be represented by the supported PHP binding, fail explicitly rather than silently narrowing or rounding it.

Do not expose `int|string` unions merely to work around protocol integer encoding.

Do not expose protocol-specific integer wrapper classes unless there is no simpler author-facing representation.

## Immutable author-facing data

Prefer public readonly promoted properties.

Good:

```php
final readonly class Asset
{
    /**
     * Create a saved asset.
     *
     * @param string $id Stable asset ID.
     * @param string $reference Opaque reference used to read the asset.
     * @param string|null $mediaType Media type when known.
     * @param int $sizeBytes File size in bytes.
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $mediaType,
        public int $sizeBytes,
    ) {}
}
```

Do not write separate property declarations, constructor assignments, and trivial getters for immutable data.

Keep properties private when access itself has behaviour, authority, laziness, or mutable state.

## Constructor property promotion

Use property promotion whenever a constructor merely assigns a same-named parameter to a property.

Do not write:

```php
private readonly string $id;

public function __construct(string $id)
{
    $this->id = $id;
}
```

when this is sufficient:

```php
public function __construct(
    private readonly string $id,
) {}
```

Generated record-like classes should follow the same rule.

## Collections

Normal PHP arrays with precise PHPDoc are fine.

Prefer:

```php
/** @param list<Asset> $assets */
```

over creating `AssetCollection`, unless the collection itself has meaningful behaviour or invariants.

Do not create collection classes merely for stronger nominal typing.

## Enums and closed sets

If a public value has a finite known set of cases, prefer a native PHP enum.

Do not expose unchecked strings such as:

```php
public string $intent;
```

when the valid values are a closed set controlled by the SDK.

Opaque IDs, references, continuation tokens, plugin-defined action names, and similar intentionally open strings should remain strings.

Do not invent semantics that the contract does not define.

## Binary data

PHP strings are the public representation for byte sequences.

Do not expose `list<int>` to plugin authors for ordinary binary data.

Conversion between protocol `list<u8>` and PHP binary strings belongs at the runtime boundary.

## Generated contract code

Generated code exists to represent the frozen protocol exactly.

Do not manually edit generated files as the primary fix.

If generated output is wrong or unnecessarily verbose, change the generator and regenerate.

Generated immutable record classes should normally:

- use constructor property promotion
- use readonly properties
- preserve field order
- preserve exact protocol types
- avoid redundant property declarations
- avoid redundant constructor assignments
- use useful constructor `@param` documentation

Generated code may be large. Raw line count is not a problem.

The public conceptual surface is the metric that matters.

## Runtime and protocol code

Runtime code may be more complicated than public code when that complexity enforces real protocol guarantees.

Keep code that protects:

- RPC framing
- resource ownership
- invocation lifetime
- staged writer transfer
- helper process cleanup
- terminal/EOF ordering
- frame-size limits
- exact variant handling
- strict protocol validation

Do not simplify these guarantees away for aesthetics.

But do not expose them to plugin authors unless they affect what the author must do.

## Helpers and long-running work

Slow work is allowed.

Silent work is not.

Helper APIs must preserve live stdout/stderr/activity reporting.

Do not buffer an entire helper process before exposing output.

Do not lose output because a plugin consumes events slowly.

Do not allow helpers to become orphaned when resources or invocations end.

Core owns job scheduling and concurrency. A plugin invocation should represent bounded work, not an immortal campaign.

## No-Core development

Plugin authors must be able to develop and test plugins without running a full Stashd Core instance.

The intended development ladder is:

1. in-process SDK tests/harnesses
2. real plugin subprocess against the SDK development host
3. optional interactive/manual testing
4. full Core for final integration testing

Do not make ordinary plugin development require a local Core installation.

## First-party plugins are forcing cases

Use the real first-party plugins to judge whether the SDK is pleasant:

- YouTube
- Podcast
- Jellyfin
- Plex

Do not add capabilities merely because they might theoretically be useful.

Add or change SDK functionality when a concrete plugin use case forces it.

If a first-party plugin requires awkward workarounds, first ask whether the SDK API is wrong.

## Comments

Write comments like a normal PHP developer.

Public documentation should use short, direct English suitable for someone seeing Stashd for the first time and for developers who may speak English as a second language.

Explain:

- what the thing is
- when the developer uses it
- unusual behaviour the developer needs to know

Do not narrate implementation details.

Avoid unnecessary wording such as:

- canonical
- host-mediated
- invocation-scoped
- protocol identity
- contract fact
- exact focused
- preserved without normalization
- assemble the complete
- terminal race

Use protocol terminology only where it genuinely helps.

Do not write comments that merely restate the method name or PHP type.

## PHPDoc

Useful multiline PHPDoc is required for:

- classes
- methods and functions
- properties
- constants
- enum cases

Promoted constructor properties may be documented by the matching constructor `@param` entry.

Example:

```php
/**
 * Create a saved asset.
 *
 * @param string $id Stable asset ID.
 * @param string $reference Opaque reference used to read the asset.
 */
public function __construct(
    public string $id,
    public string $reference,
) {}
```

Do not add a second redundant docblock above each promoted property.

Keep PHPDoc that static analysis needs, especially `list<T>` and shaped arrays.

## Error handling

Protocol violations and ordinary operational failures are different things.

A malformed or impossible host/plugin message is a protocol violation.

An allowed operation that fails normally should use the appropriate typed failure path.

Do not turn normal failures into protocol violations merely because they are inconvenient.

Error messages should say what is wrong in plain language.

## Dependencies

Do not add dependencies casually.

Use a mature package when it replaces a generic solved problem and materially removes maintenance burden.

Do not add a framework to avoid writing a small amount of Stashd-specific code.

Before adding a dependency, ask:

1. Is this generic infrastructure rather than Stashd semantics?
2. Does the package model our actual requirements?
3. Does it remove meaningful code?
4. Are we comfortable exposing its types in our public API?
5. Is the maintenance/release story healthy?

Do not introduce dependencies during unrelated cleanup work.

## Duplication and transitional code

This branch has gone through several API iterations.

Delete obsolete generations of an idea.

Do not keep:

- old and new names for the same concept
- aliases returning the same value
- duplicate interfaces
- transitional DTOs
- compatibility shims for unreleased APIs
- tests whose only purpose is preserving obsolete ceremony

If two public APIs do the same thing, choose one.

## Testing

Tests should protect behaviour and useful API guarantees.

Do not test implementation ceremony simply because it exists.

When changing generated code, verify regeneration is reproducible.

When changing runtime/protocol behaviour, add focused regression tests.

When changing public APIs, update starter plugins and examples in the same work.

Run the relevant:

- test suite
- static analysis
- documentation checker
- formatting/style checks
- generator consistency checks

Do not fix unrelated failures unless they block the requested work. Report them clearly.

## Scope discipline

Do the requested task.

Do not turn a focused cleanup into an architecture project.

Do not add speculative features.

Do not rewrite unrelated working code merely because you noticed it.

When the requested change exposes obvious dead code or transitional duplication directly adjacent to the work, remove it if doing so is safe and clearly simplifies the result.

## Before adding code

Ask:

- Can this be deleted instead?
- Can native PHP express this?
- Is this a real Stashd concept or just protocol plumbing?
- Does the plugin author need to know this exists?
- Are we creating a second representation of something we already have?
- Would the first-party plugins actually use this?

Prefer the smallest correct answer.

## Definition of done

A change is complete when:

- contract behaviour remains correct
- the public API is no more complicated than necessary
- generated changes come from the generator
- comments read like normal human documentation
- examples/starters reflect changed public APIs
- relevant tests and checks pass
- no obsolete compatibility layer was left behind without a reason

The goal is not an impressive SDK.

The goal is an SDK that gets out of the plugin author's way.