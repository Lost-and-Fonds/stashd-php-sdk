# Stashd PHP SDK 0.4.x — repository instructions

This branch is a clean rewrite of the PHP SDK for the frozen language-neutral
contract `stashd:plugin@0.17.0`.

The 0.3.x implementation is history, not an architecture template. Git preserves
it. Do not restore old classes, wire aliases, lifecycle phases, or compatibility
shims unless the frozen plugin-api contract independently requires them.

## Source of truth

The canonical contract is the `Lost-and-Fonds/plugin-api` repository at
`stashd:plugin@0.17.0`.

When SDK design, existing PHP code, examples, comments, tests, or intuition
disagree with plugin-api 0.17, plugin-api wins.

Do not modify plugin-api from this repository. Do not redesign the contract
while implementing the SDK.

## Product target

- SDK line: `stashd/php-sdk 0.4.x`
- Contract identity: `stashd:plugin@0.17.0`
- Language target: PHP 8.5+
- This is a from-scratch authoring SDK, not an in-place migration of 0.3.x.

Package version and contract identity are independent concepts.

## Design goal

Build an idiomatic PHP 8.5 authoring SDK over an exact, mostly invisible
implementation of the frozen contract.

Plugin authors should work with typed PHP objects and focused lifecycle
interfaces. They should not need to know that JSON frames, `$resource`
objects, correlation IDs, or WIT encoding exist.

At the wire boundary, correctness beats convenience. Above the wire boundary,
ergonomics and type safety matter.

A useful layering model is:

1. author-facing lifecycle interfaces and immutable typed values;
2. canonical contract model;
3. invocation/runtime/resource machinery;
4. framing and exact JSON/WIT codecs.

Keep these responsibilities separated.

## PHP 8.5

Use PHP 8.5 idiomatically and deliberately.

Prefer modern features when they make the API clearer, safer, smaller, or more
immutable. Examples include readonly/final value objects, constructor promotion,
enums, clone-with, first-class callables, and `#[\NoDiscard]` where silently
ignoring a returned value is dangerous.

Do not preserve older-PHP patterns for compatibility. Do not use new syntax only
because it is new.

Opaque contract values remain opaque. Do not trim, case-fold, URI-normalize, or
otherwise reinterpret IDs, references, schema identifiers, credential
references, continuation values, or plugin-owned state unless plugin-api
explicitly requires it.

## Public API layout — root `src/` is the shopfront

Treat the repository root `src/` namespace as the obvious starting point for a third-party plugin developer.

The small set of primary developer-facing entry interfaces/classes SHOULD live directly under:

`Stashd\\PluginSdk\\`

Examples include:

- `InputPlugin`;
- `BroadcastPlugin`;
- `EnrichmentPlugin`;
- `CollectionExporter`;
- a common bootstrap/entrypoint type if one is genuinely useful.

A plugin author should be able to open `src/` and immediately see the handful of things they are expected to implement or invoke.

Do **not** flatten every public value type into the root namespace. Supporting author-facing values should remain grouped by coherent domain, for example:

- `Stashd\\PluginSdk\\Input\\...`;
- `Stashd\\PluginSdk\\Broadcast\\...`;
- `Stashd\\PluginSdk\\Enrichment\\...`;
- `Stashd\\PluginSdk\\CollectionExport\\...`;
- `Stashd\\PluginSdk\\Shared\\...`.

Internal machinery belongs behind clearly internal-looking namespaces such as:

- `Contract\\` for exact frozen WIT/contract representations;
- `Runtime\\` for RPC/resource/process machinery;
- `Diagnostics\\` for tracing internals;
- `Tooling\\` for repository/build tooling.

The generated exact `Contract\\*` layer is **not** the intended plugin-author API. It exists to keep the wire/runtime exact. Most third-party plugin code should never need to import it.

Design for this mental model:

- root `src/`: “start here”;
- domain namespaces: “things you use while implementing it”;
- `Contract/Runtime/Tooling`: “storage room; normally do not touch”.

Do not let `src/` become a flat junk drawer again. Only primary interaction points belong at the root.

## Public API rules

- Public authoring APIs must be typed. Do not expose associative-array wire
  structures as the normal plugin-author API.
- Prefer immutable value objects.
- Prefer lifecycle-specific capability surfaces over one giant god context when
  that prevents illegal operations from being representable.
- Do not create a new god `WireMapper`. Keep codecs/mappers focused by protocol
  or contract area.
- Resource handles are runtime implementation details. Authors should see typed
  objects such as streams, writers, collections, reporters, and HTTP clients.
- Do not invent filesystem paths, process environment conventions, callback
  servers, or other semantics not granted by the contract.
- Do not preserve 0.3.x public API compatibility merely to reduce churn. 0.4.0
  is the deliberate breaking rewrite.

## Documentation audience and generation rules

Public author-facing documentation and example code MUST use simple, direct English suitable for a developer who has never used Stashd before and may speak English as a second language.

For public SDK PHPDoc, READMEs, starter plugins, tutorials, and examples:

- prefer short sentences and common words;
- explain what something does before explaining how it is implemented;
- define Stashd-specific terms when the reader first needs them;
- avoid internal architecture language such as RPC, WIT, wire, lifecycle, host capability, resource handle, author-facing surface, or contract representation unless that detail is necessary for the task being explained;
- do not write as if the reader followed the SDK's design process;
- do not refer to implementation tradeoffs that only SDK maintainers need to know;
- prefer concrete verbs such as “save”, “find”, “publish”, “read”, “write”, and “return” over abstract phrases such as “execute the lifecycle” or “consume the selected collection”;
- keep precision where it matters, but do not use specialist language merely because the implementation uses it.

A useful test is: a PHP developer seeing Stashd for the first time should understand the comment without reading the protocol repository.

Documentation quality is part of the SDK design, not just a CI checkbox.

Generated PHPDoc MUST be written for a human reader and must explain the contract meaning of the declaration. Generic filler such as “Canonical id value”, “Gets the value”, “Immutable contract fact”, or “retained in contract order” is not sufficient by itself even if it satisfies the mechanical prose checker.

Use this priority order for generated contract documentation:

1. **Normative WIT/protocol documentation first.**
   - Preserve and adapt the frozen plugin-api comments that explain identity, opacity, ownership, lifetime, authority, batching, ordering, retryability, null meaning, invariants, or other semantics.
   - Lightly adapt wording for a PHP reader where necessary, but do not weaken or invent semantics.
   - When relevant semantics live in a normative protocol document rather than directly beside the WIT declaration, the generator or a small explicit documentation mapping MAY supply that text.

2. **Audience-aware semantic fallback text second.**
   - If the frozen contract supplies no useful prose, generate a description based on the declaration's role and type rather than its spelling alone.
   - Opaque strings should say that they are opaque, who owns/interprets them, and that the SDK preserves them verbatim.
   - Resources should explain invocation scope, ownership/borrowing, explicit release, and stale-handle behavior where relevant.
   - Lists should explain ordering, duplicate significance, and ownership/interpretation where known.
   - Optional values should explain what `null` means when the contract establishes that meaning.
   - Enum/variant cases should explain their protocol identity or semantic branch where known.
   - Quantities should state units and bounds where relevant.
   - References must not be described as paths, URLs, bearer credentials, or other stronger concepts unless the contract says so.

3. **Hand-written author-facing documentation for the shopfront API.**
   - Primary root interfaces/classes and domain-facing author types MUST be deliberately documented for third-party PHP plugin developers.
   - Do not generate vague contract-shaped prose for the main author experience merely because generation is convenient.
   - Explain when plugin authors implement/call the API, what the host supplies, what the plugin owns, what may fail, what is opaque, what lifetime applies, and any ordering/retry/security implications.

Generated `Contract\\*` types should make their audience explicit. Where useful, their type-level docs should say that they are internal exact representations used by runtime/codecs to preserve the frozen contract and that plugin authors normally use the corresponding author-facing SDK API instead.

The generator is the source of truth for generated documentation. Do not hand-edit hundreds of generated PHPDoc blocks: improve `tools/generate-contract.py`, its input documentation data, or a small explicit semantic documentation map so regeneration remains deterministic.

A small number of hand-maintained semantic overrides is acceptable for important concepts whose meaning cannot be recovered safely from the parsed WIT alone, for example preserved/staged Assets, discovery continuation/refresh state, lifecycle interfaces, and resource ownership types. Keep such overrides explicit, reviewable, and tied to the frozen contract rather than duplicating arbitrary prose across generated files.

The mechanical documentation checker proves that description prose exists. Human review MUST additionally reject generated boilerplate that fails to explain useful semantics.

## Mandatory documentation — CI invariant

EVERY PHP declaration must have PHPDoc with real description prose.

This includes every:

- class;
- interface;
- enum;
- trait;
- named function;
- method, including constructors and private methods;
- property, including protected/private and promoted properties.

Each required docblock MUST contain at least one non-empty human-readable
description line. Tags do not count as description text.

These are CI failures:

- missing docblock;
- empty docblock;
- whitespace-only docblock;
- annotation-only docblock such as `/** @var string */`;
- a promoted property whose documentation is not visible to the repository
  documentation checker.

If constructor promotion makes correct property documentation awkward, declare
the property explicitly instead.

Description prose must explain semantics a third-party developer may need, not
merely restate the symbol name. Depending on the declaration, cover things such
as purpose, lifecycle, ownership, scope, units, opacity, invariants, valid and
invalid states, side effects, ordering, retry semantics, failure modes,
security/credential sensitivity, and the corresponding plugin-api concept.

The mechanical checker proves that description prose exists. Review is
responsible for rejecting useless prose such as "The ID" or "Gets the value."

New or modified PHP code is not complete until this rule passes.

## Errors and contract violations

Keep ordinary typed lifecycle outcomes, host-capability failures, and
protocol/contract violations distinct.

A contract/protocol violation must never be quietly converted into a normal
plugin-authored lifecycle error just to keep execution going.

Validation should fail as close as possible to the violated boundary and
diagnostics should identify the actual invariant.

## Diagnostic tracing

The 0.4.x runtime must provide opt-in exhaustive tracing to STDERR, never RPC
STDOUT.

There must be one obvious maximum switch. The preferred spelling is:

`STASHD_PHP_SDK_TRACE=ludicrous`

A smaller level set such as `off|basic|verbose|wire|ludicrous` is welcome if
useful, but `ludicrous` means "give me essentially everything useful."

When tracing is enabled, make runtime behavior reconstructable: hello
negotiation, invocation boundaries, frame direction/size/IDs, dispatch,
re-entrant calls, resource create/borrow/transfer/drop, stream activity, staging
state, validation decisions, typed failures, protocol failures, elapsed timing,
and invocation cleanup.

Tracing may be expensive. That is acceptable. Disabled tracing should have
minimal overhead.

Secrets are NEVER fair game. Always redact credential values, raw secrets,
Authorization/Cookie-style headers, helper credential environment values, and
other known secret material, even at maximum tracing.

Large/sensitive bodies should default to metadata such as byte counts, IDs, and
optional hashes rather than full contents. If a separate raw-payload switch is
implemented, secret redaction still applies.

Do not impose a hidden SDK log-volume cap. Rotation/storage is an outer runtime
concern.

## Wire/runtime invariants

Implement plugin-api 0.17 exactly, including its framing, hello negotiation,
directional frame maxima, exact JSON value mapping, duplicate-object-member
rejection, invocation IDs, response envelopes, resource ownership/borrowing,
re-entrant host capability calls, cleanup, byte-stream behavior, byte ranges,
metadata validation, staging, credentials, Input, Broadcast, Enrichment, and
Collection Export semantics.

Do not create a PHP-specific RPC dialect.

In particular, do not resurrect:

- top-level RPC `error` responses where canonical RPC uses `result`;
- base64 encoding for canonical `list<u8>`;
- fixed private frame ceilings below negotiated limits;
- non-WIT-qualified lifecycle method aliases;
- string pseudo-resource handles;
- Broadcast prepare/finalize;
- complete inline Broadcast item lists;
- media-specific universal Input fields/roles;
- path-based staging as a protocol abstraction.

## Testing and CI

The finished rewrite must have strong CI covering at least:

- Composer validation;
- formatting/lint;
- static analysis at the strongest practical level;
- unit/feature/conformance tests;
- mandatory PHPDoc-with-description enforcement;
- plugin-api 0.17 language-neutral vectors/invariants where applicable;
- explicit rejection of important 0.3.x wire conventions.

Tests should exercise hostile malformed input as well as happy paths.

A green test suite must mean something stronger than "our PHP objects agree
with our own mapper."

## Scope discipline

Keep this repository domain-neutral. It owns the PHP authoring/runtime SDK, not
Stashd Core behavior and not provider behavior.

Do not add YouTube, podcast, document, Jellyfin, Plex, S3, or other provider
semantics to generic SDK types merely because those domains are useful forcing
examples.

Do not add future extensibility abstractions without a current contract or
authoring need.

## Working practice

Keep changes coherent and reviewable even when doing a broad bootstrap pass.

Before considering work complete:

1. inspect the frozen plugin-api source for the relevant behavior;
2. run all repository verification commands;
3. run static analysis;
4. run formatting/lint checks;
5. inspect the diff for accidental compatibility fossils and undocumented
   declarations;
6. report exactly what was implemented, what remains, and any contract question
   that could not be resolved from plugin-api.

If the contract appears ambiguous, stop inventing semantics and surface the
specific ambiguity.
