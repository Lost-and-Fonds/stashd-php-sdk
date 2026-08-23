# Stashd plugin SDK

This package is the author-facing PHP 8.5 surface for plugin Stashd plugins. It
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

See `examples/minimal-broadcast/` for the smallest complete plugin shape. Run
the SDK checks with `./tests/run.sh`.
