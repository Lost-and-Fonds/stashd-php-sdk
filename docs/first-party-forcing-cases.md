# First-party plugin forcing cases

This note checks the intended behaviour of Stashd's current first-party plugins against the frozen `stashd:plugin@0.17.0` contract and the PHP SDK 0.4 authoring design.

The goal is not to preserve old implementation patterns. The old plugins contain filesystem paths, giant context objects, preparation phases, provider-shaped DTOs, and other 0.3-era workarounds that must not become new SDK requirements.

The question is: can a clean 0.4 plugin express what the provider is trying to do without dropping into `Contract` or `Runtime` internals?

## YouTube Input

The YouTube plugin needs to:

- resolve channel, handle, playlist, video, Shorts, mobile, and YouTube Music references into stable Input identity;
- perform lightweight refresh discovery from Atom feeds;
- perform complete enumeration through the YouTube Data API when a credential is available;
- fall back to a host-approved yt-dlp helper for complete enumeration;
- discover very large channels without returning one enormous inline array;
- filter Shorts, live streams, premieres, captions, and languages from plugin-owned options;
- report partial or unavailable upstream Items without losing valid discoveries;
- acquire long videos and audio through yt-dlp/ffmpeg;
- preserve primary media, metadata, artwork, and captions as separate staged artifacts;
- associate metadata such as title, description, publication date, duration, upstream state, role, and caption language through versioned plugin metadata facets;
- use host-mediated HTTP credentials;
- pass cookies/session or PO-token material to an approved helper without putting secret material in arguments or metadata;
- show long-running acquisition as alive while helper/stream work continues.

### 0.4 SDK expectations

The Input authoring API must make bounded discovery commits natural. An author should be able to add Items one at a time or in their own provider page size while the SDK respects the host's maximum batch size and frame limits.

Credential availability must be inspectable without magic options such as the old `__stashd_complete_credential_available`.

The helper API must make the canonical stream model pleasant:

- Asset/staged streams should feel like ordinary readable PHP byte streams or iterables;
- a staged writer should be easy to pass as helper stdout;
- a helper result written to temporary invocation staging should be easy to finish, reopen, and parse;
- helpers that produce several durable outputs may be invoked once per output without exposing a host filesystem path;
- selected credential bindings should be easy to pass by slot name.

The SDK must not recreate `/staging`, `stagingPath`, helper executable paths, or other fake filesystem capabilities.

### Deliberate redesigns

The existing SQLite size estimator uses a persistent plugin-data path that 0.17 does not grant. It is an optimisation, not correctness state. The migrated plugin should use deterministic estimates or another explicitly supported durable mechanism rather than forcing a PHP-only persistent directory into the SDK.

The current yt-dlp helper emits live progress text while downloading. Canonical `run-helper` does not stream helper diagnostic stdout/stderr back to plugin code while the helper runs. Long helper work can still remain alive through host-observable activity and the plugin can report an indeterminate “Downloading” stage before starting it, but exact live yt-dlp percentages are not something the SDK should fake.

## Podcast package

The Podcast package needs to:

- publish RSS/Podcasting 2.0 feeds from preserved Items;
- select audio or video Assets;
- derive MP3 audio from video when needed;
- convert caption Assets into durable text transcripts;
- include plugin-owned Item metadata such as title, description, publication date, duration, artwork, funding and feed fields;
- create one staged feed artifact;
- export small podcast subscription collections as OPML.

### 0.4 SDK expectations

The clean model is a multi-component package:

- an Enrichment component can derive audio and transcript Assets with canonical provenance;
- a Broadcast component can build the feed from already-preserved Assets and metadata;
- a Collection Export component can create OPML for small subscription lists.

The SDK must make multi-component packages ordinary. A developer may use separate tiny launchers per component or a small safe bootstrap convenience; they should not need an old global plugin registry or hidden component-selection state.

Enrichment must make this flow easy:

`preserved Asset -> open stream -> helper/PHP transform -> staged writer -> derived Asset`.

Broadcast must make bounded Item traversal and metadata reading easy, and allow a final feed document to be staged without exposing storage paths.

Collection Export should remain deliberately small and one-shot. Large catalogues belong to Broadcast.

Automatic scheduling of an Enrichment because a Broadcast needs a derived Asset is Core/application orchestration, not an SDK wire feature.

## Jellyfin Broadcast

The Jellyfin plugin needs to:

- consume selected video Assets;
- inspect Item metadata needed for chronology and naming;
- produce a complete filesystem-relative mapping;
- apply destination configuration such as server/library choices;
- authenticate remote HTTP operations through an invocation credential;
- test a connection;
- list remote libraries;
- trigger a library refresh.

### 0.4 SDK expectations

`Broadcast\Publish` should expose the selected Item collection as a simple iterable with optional `batchSize`.

Because canonical collection order has no domain meaning, the SDK must not imply that it is chronological. Plugins that need global ordering must be able to deliberately collect/buffer the selected Items and sort them.

Reporting filesystem paths should feel like `reportFile(...)`; the SDK should batch reporter calls internally or through an obvious batching helper while preserving canonical limits and atomicity.

`Broadcast\Operation` should make connection tests and library discovery straightforward with typed settings, payload, choices, credentials, and HTTP.

## Plex Broadcast

Plex needs the same Broadcast behaviour as Jellyfin, plus:

- optional caption sidecar mapping;
- one generated XML/NFO artifact;
- media-type-aware file extensions.

The same SDK requirements apply. Asset role/language/title/date/source grouping are metadata semantics, not new universal Broadcast fields.

## Cross-plugin metadata

The 0.17 Broadcast Item is intentionally generic. It does not contain universal title, description, date, duration, media role, language, source reference, public URL, or audiovisual kind fields.

First-party plugins still need those facts.

They must therefore be carried in stable, versioned metadata facet schemas understood by the first-party producers and consumers. The generic PHP SDK should provide pleasant metadata creation, lookup, decoding, and validation helpers, but it MUST NOT bake YouTube/media/podcast fields into universal SDK value types.

Source-specific destination information such as “this selected source is season 3” also needs an application-level representation, likely an invocation metadata facet or destination configuration convention. Do not restore a universal `sourceReference` wire field merely for Jellyfin/Plex.

## Application integration outside the PHP SDK

The frozen 0.17 package manifest intentionally owns only package/component identity, artifact/world selection, exact contract identity, and component credential slots. It does not declare the old Stashd-specific UI fields, helper policy, HTTP grants, source forms, Broadcast forms, actions, or jobs.

The frozen contract also leaves URL authorization, helper approval, timeout policy, destination persistence, and application orchestration to the host/runtime.

Therefore the Stashd Core migration still needs a separate application-integration design for at least:

- source and destination configuration schema/presentation;
- helper declaration/approval and package materialisation;
- HTTP/network grant policy, including configured connection endpoints;
- first-party metadata schema conventions;
- per-source destination context such as Plex/Jellyfin season mapping;
- orchestration of required Enrichment before Broadcast;
- the sequencing of filesystem materialisation and Jellyfin/Plex refresh.

These are not reasons to contaminate `stashd:plugin@0.17.0` or the PHP SDK with old manifest fields.

## Refresh sequencing warning

The old Jellyfin/Plex plugins used a `finalize()` phase after publication to refresh the remote library. 0.17 deliberately has no finalize phase and says required destination work occurs inside `publish()`.

Core must therefore ensure its filesystem publication semantics make this valid. If reported filesystem mappings are not materialised until after `publish()` returns, a refresh performed inside `publish()` would happen too early.

This must be resolved in Core/runtime sequencing. The SDK must not resurrect `finalize()` as a PHP-only lifecycle.

## Release forcing rule

The 0.4 author API is not considered pleasant merely because the exact Contract layer can technically encode these providers.

Before 0.4.0, representative tests/examples must demonstrate the intended first-party flows using only public author APIs:

- large, batched Input discovery and helper-backed acquisition;
- stream-based helper input/output with credentials and temporary staging;
- metadata facet reading/writing;
- Enrichment-derived Assets;
- multi-component package bootstrap;
- bounded Broadcast traversal and large file reporting;
- connection/library operations;
- Podcast-style staged publication;
- small OPML-style Collection Export.

None of these examples may require imports from `Contract`, `Runtime`, `Diagnostics`, or `Tooling`.
