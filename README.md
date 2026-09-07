# Stashd PHP SDK

This package is the author-facing PHP 8.5 surface for Stashd plugins. It
contains only contract DTOs, lifecycle interfaces, capability interfaces, plugin
RPC bootstrap, and wire mapping. The canonical contract is published separately
as `stashd/plugin-api`; this package implements that authoring surface.

The bootstrap keeps framing and process mechanics out of plugin code;
supervision, Bubblewrap, package activation, and Stashd application services
remain runtime concerns outside this package.

The package is independently versionable and requires PHP 8.5 or newer. Its
PSR-4 autoloading keeps the package usable without a framework or service
container. The plugin runner mounts this package read-only as `/sdk` for an
invocation; an entrypoint only needs to require `/sdk/bootstrap.php`.

Broadcast and Input entrypoints use the same framed runtime boundary;
`Runtime\InputPluginServer` supplies HTTP, credential, helper, staging, log,
and progress capabilities to Input providers. Broadcast lifecycle methods each
receive an invocation-scoped `PluginContext`; `PublishRequest` contains only
contract data. `StagedArtifact::role` carries generic primary, metadata,
artwork, and caption roles without provider types.

The current binding targets `stashd:plugin@0.2.0`. RPC v1 remains a
four-byte big-endian length-prefixed UTF-8 JSON stream. Typed
`PluginFailureException` values are serialized as the contract's `{tag,value}`
error variant; ordinary exceptions become `failed` with `retryable: false`.

See `examples/minimal-broadcast/` for the smallest complete plugin shape. Run
the SDK checks with `composer test`.
