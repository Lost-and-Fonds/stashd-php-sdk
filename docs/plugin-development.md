# Plugin development without Stashd Core

A plugin developer should not need a running Stashd Core instance for normal development.

The SDK should support three levels of testing.

## 1. Fast PHP tests

Use the SDK testing helpers in `Stashd\PluginSdk\Testing\...`.

Each plugin type should have one obvious harness:

- `InputHarness`
- `BroadcastHarness`
- `EnrichmentHarness`
- `CollectionExportHarness`

These helpers call the same public interfaces used by real plugins.

They should make common test setup easy. A test should be able to provide fake HTTP responses, credentials, saved Assets, helper results, and staged output without knowing how Stashd RPC works.

For example:

```php
$harness = InputHarness::for(new ExampleInputPlugin());

$input = $harness->resolve([
    'name' => 'demo',
]);

$items = $harness->discover($input);

expect($items)->toHaveCount(2);
```

The exact API may change while 0.4 is built. The goal is the important part: normal plugin tests are plain PHP tests.

## 2. Test the real plugin process

The SDK should also include a small development host.

Run:

```bash
vendor/bin/stashd-plugin check
```

This should read `stashd-plugin.json`, start the real plugin executable, and check that the package can speak the Stashd plugin protocol.

It should catch problems that an in-process test cannot catch, such as:

- an invalid manifest;
- a missing or non-runnable artifact;
- broken Composer autoloading;
- startup errors;
- failed protocol negotiation;
- a declared world that the process cannot serve;
- protocol or resource cleanup errors.

This tool must use the real plugin process and canonical protocol rules. It must not use a simplified test-only protocol.

## 3. Try the plugin by hand

A command such as:

```bash
vendor/bin/stashd-plugin try
```

should make it easy to call a plugin by hand without starting Core.

For example, an Input plugin could offer choices such as:

```text
What would you like to test?

1. Resolve an input
2. Find items
3. Save an item
```

The tool should ask for simple values and show readable results.

Repeatable fixture files should also be supported when useful.

## When Core is needed

A full Stashd Core instance is still useful for final end-to-end tests.

That is where a developer checks things such as real configuration, Core storage, UI behavior, scheduling, and full package installation.

Core should not be needed for the normal edit-test loop.

## Starter plugins

The private example repositories are forcing examples for this workflow:

- `Lost-and-Fonds/example-input-plugin`
- `Lost-and-Fonds/example-broadcast-plugin`
- `Lost-and-Fonds/example-enrichment-plugin`
- `Lost-and-Fonds/example-collection-export-plugin`

The finished SDK should make these examples work without exposing internal `Contract`, `Runtime`, `Diagnostics`, or `Tooling` APIs.

Their normal development flow should be:

```bash
composer install
composer test
vendor/bin/stashd-plugin check
```

Running a full Stashd Core instance is optional.
