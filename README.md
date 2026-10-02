# Stashd PHP SDK

This branch is the clean-room 0.4.x rewrite of the Stashd PHP authoring SDK for
the frozen `stashd:plugin@0.18.0` contract.

The previous 0.3.x implementation was intentionally removed before the rewrite
so obsolete RPC, lifecycle, media-model, and staging assumptions cannot survive
by accident.

The rewrite first targeted 0.17. That contract buffered helper output until
completion, so it could not show real yt-dlp or ffmpeg progress while running.
Plugin-api 0.18 replaces that helper call with a live process and is the only
implementation target. The SDK package version (0.4.x), the plugin-api Composer
package version, and the WIT identity above are separate.

Implementation starts from the language-neutral plugin contract. Repository-wide
engineering rules are in [AGENTS.md](AGENTS.md). The [job execution](docs/job-execution.md)
and [first-party examples](docs/first-party-forcing-cases.md) notes record the
planned developer experience. This pass aligns the plan; it does not implement
the SDK or make the starter plugins runnable yet.

## First implementation slice after review

Update the pinned plugin-api schema and language-neutral vectors to 0.18,
then regenerate and review the exact contract types/codecs. Replace the stale
0.17 helper result with the canonical process event, terminal and owned writer
shapes. Exercise resource transfer, frame-sized live output, staged activity,
cancellation, cleanup and secret-safe traces before adding the public PHP helper
API. The current pinned Composer package, schema snapshot, generator guard and
generated helper result still describe 0.17; passing scaffold tests do not
establish 0.18 conformance. Do not start this slice during synchronization.
