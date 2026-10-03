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
planned developer experience. The rewrite remains incomplete; the starter
plugins are not runnable yet.

## Live helpers

A Broadcast operation can use the host-approved helper capability during its
invocation. The public `Helpers` entry point stages output and starts a process;
`Process::events()` reads events lazily. `Output::bytes` is raw binary data,
including carriage returns. `StdoutActivity::bytes` is a cumulative count, not
provider progress. `Exited::output` returns a writer only after normal exit:

```php
$saved = '';
$writer = $helpers->stage('application/octet-stream');
$process = $helpers->start('approved-helper', ['--progress'], output: $writer);
foreach ($process->events() as $event) {
    if ($event instanceof \Stashd\PluginSdk\Helper\Output) {
        $bytes = $event->bytes;
    } elseif ($event instanceof \Stashd\PluginSdk\Helper\Exited && $event->output !== null) {
        foreach ($event->output->finish()->chunks() as $chunk) {
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

The vendored schema and vectors now target frozen 0.18 independently of the
plugin-api Composer package version. Exact contract declarations include the
live helper-process events and terminal outcomes. Internal runtime code tracks
owned resource transfers, staged writer return, process event order and safe
diagnostics. A small public helper API now uses this foundation, but no host
subprocess implementation exists yet. Host pipe draining and backpressure still require real-host
conformance tests before release.
