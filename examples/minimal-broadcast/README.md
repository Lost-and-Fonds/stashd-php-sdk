# Minimal Broadcast example

`MinimalBroadcast.php` shows the four methods required by the SDK's
`BroadcastPlugin` interface. Every lifecycle call receives an invocation-scoped
`PluginContext`; a real package adds a `stashd-plugin/` manifest and entrypoint
and declares `stashd/php-sdk` as a Composer dependency.
