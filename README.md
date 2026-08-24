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
and progress capabilities to Input providers. `StagedArtifact::role` carries
generic primary, metadata, artwork, and caption roles without provider types.

See `examples/minimal-broadcast/` for the smallest complete plugin shape. Run
the SDK checks with `composer test`.
