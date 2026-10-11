# Stashd PHP SDK

This branch is the clean-room 0.4.x rewrite of the Stashd PHP authoring SDK for
the `stashd:plugin@0.19.0` contract at plugin-api commit
`585e4a377935d9ec46b38572f65e6ec72983860b`.

The previous 0.3.x implementation was intentionally removed before the rewrite
so obsolete RPC, lifecycle, media-model, and staging assumptions cannot survive
by accident.

The rewrite first targeted 0.17. That contract buffered helper output until
completion, so it could not show real yt-dlp or ffmpeg progress while running.
Plugin-api 0.19 provides live bounded polling and invocation cancellation. The SDK package version (0.4.x), the plugin-api Composer
package version, and the WIT identity above are separate.

Implementation starts from the language-neutral plugin contract. Repository-wide
engineering rules are in [AGENTS.md](AGENTS.md). The [job execution](docs/job-execution.md)
and [first-party examples](docs/first-party-forcing-cases.md) notes record the
planned developer experience. The rewrite remains incomplete; the starter
plugins are not runnable yet.

## Input sources

Inputs implement `canResolve(Source $source): bool` and
`resolve(Resolve $request): ResolvedInput`. The probe receives only the source;
it must be cheap and deterministic, with no credentials, network calls, state
mutation, or helpers. Normal resolution receives `$request->source` and can
use `$request->tools()` and `$request->credentials()`.

```php
$source = new Source('opaque-reference');
$configured = new Source(values: [new Option('catalog', 'selected-catalog')]);
$item = new DiscoveredItem('stable-item', 'acquisition-reference', source: $source);
```

These types live in `Stashd\PluginSdk\Input`. A source has a nullable reference
and ordered typed values; Core does not interpret either. Direct and discovered
references take the same resolution path. There is no delegated request type
or target-plugin selector. Core retains provenance and selects an accepting
Input. Discovery and acquisition settings are ordinary `list<Option>` arrays.
Throw `Input\Failure` with an `Input\ErrorKind`, message, and retryable flag
for normal resolution, discovery, or acquisition failures. Probe exceptions
remain execution failures, never false or typed lifecycle errors.

The dependency is pinned to the verified 0.19 tag commit. Its upstream Composer
metadata still declares 0.2.2, so this checkout uses an explicit package repository
for that tag. Downstream applications must configure the same repository until
upstream publishes corrected Composer metadata; Composer does not inherit dependency
repository definitions. `composer check:contract` checks installed provenance and
compares snapshots with the installed dependency before checking generated PHP.

## Live helpers

A Broadcast operation can use the host-approved helper capability during its
invocation. The public `Tools` entry point stages output and starts a process;
`Process::events()` reads events lazily. `Output::bytes` is raw binary data,
including carriage returns. `StdoutActivity::bytes` is a cumulative count, not
provider progress. `Exited::output` returns a writer only after normal exit:

```php
$saved = '';
$writer = $helpers->stage('application/octet-stream');
$process = $helpers->start('approved-helper', ['--progress'], output: $writer);
foreach ($process->events() as $event) {
    if ($event instanceof \Stashd\PluginSdk\Helper\CancelRequested) {
        $process->cancel();
    } elseif ($event instanceof \Stashd\PluginSdk\Helper\Output) {
        $bytes = $event->bytes;
    } elseif ($event instanceof \Stashd\PluginSdk\Helper\Exited && $event->output !== null) {
        $artifact = $event->output->finish();
        $event->output->close();
        foreach ($artifact->chunks() as $chunk) {
            $saved .= $chunk;
        }
    }
}
$process->close();
```

The full provider-progress example is in `examples/ProgressBroadcast.php`.
The normal Broadcast operation runner and fake-host test exercise the canonical
RPC path. Real OS process draining and backpressure are not implemented here.

## Contract foundation

The vendored schema and vectors are checked against the exact installed 0.19 contract. Exact contract declarations include the
live helper-process events and terminal outcomes. Internal runtime code tracks
owned resource transfers, staged writer return, process event order and safe
diagnostics. A small public helper API now uses this foundation, but no host
subprocess implementation exists yet. Host pipe draining and backpressure still require real-host
conformance tests before release.

## Polling and lifecycle guarantees

`poll($waitMs = 1000)` returns `cancelRequested`, `ended`, and an optional event.
A null event without end means pending. Zero waits poll immediately. `events()`
yields one `CancelRequested` notification per iteration before the accompanying
event, including cancellation first observed at end. It does not cancel helpers
automatically; authors cancel appropriate helpers and continue draining output.
Explicit polls after end still observe sticky invocation cancellation.

Every applicable request exposes `tools()`. Writer writes and publication reports
split to fit negotiated frames. Multi-frame operations are not atomic as a whole.
Successful writer finish keeps its handle owned until close or invocation cleanup;
closing that handle does not invalidate its finished artifact. Publication must
consume collection EOF; discovery must receive terminal acknowledgement.

Core integration must verify independent pipe draining/spooling, cancellation
wakeups, child reaping, canonical artifact adoption and preserved-asset grants.
SDK fake-host tests do not establish those host guarantees.
