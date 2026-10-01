# Job execution, batching, and liveness

This note records the execution model expected by the PHP SDK developer tools and by Stashd Core.

It does not change the frozen `stashd:plugin@0.17.0` wire contract. Where the contract is more specific, the contract wins.

## One invocation is one bounded piece of work

Do not treat a large preservation job as one giant plugin invocation.

For example, adding a channel with thousands of videos should normally mean:

1. resolve the channel;
2. discover items in batches;
3. commit discovered items as discovery progresses;
4. schedule each discovered item for its own acquisition work.

A single failed acquisition must not poison the whole collection.

## Discovery is pipelined

A committed discovery batch is usable immediately.

Core may begin acquiring committed items while the plugin continues discovering later items.

For example:

```text
discover batch 1
    -> Core queues those items
discover batch 2
    -> acquisitions from batch 1 may already be running
discover batch 3
    -> queue continues to fill
```

Only committed items are eligible. Items that exist only inside plugin memory are not yet host-visible work.

Concurrency is controlled by the host. The plugin does not decide how many acquisitions run at once.

## Why batching exists

Batching is a transfer and acknowledgement mechanism, not a claim that a batch is a meaningful domain object.

It:

- avoids one RPC round trip per item;
- keeps messages bounded;
- gives the host backpressure;
- creates clear progress checkpoints;
- lets large discoveries resume from durable progress instead of starting again.

The author-facing SDK may offer item-at-a-time ergonomics while batching internally.

Where the public SDK exposes a batch-size preference, use the consistent name `batchSize`.

Rules for `batchSize`:

- it is optional;
- the SDK chooses a sensible default when omitted;
- a plugin may request a different size;
- the host or protocol may cap the requested size;
- it changes transfer size only, not item meaning or ordering.

Explicit commit/flush operations may still be useful when the plugin knows it has reached a meaningful checkpoint.

## Liveness and timeouts

There should not be one short wall-clock timeout for all plugin work.

Use two separate protections.

### Idle watchdog

An invocation may run for a long time while it is making meaningful progress.

Meaningful progress includes work the host can observe, such as:

- committed discovery batches;
- bytes moving through host-managed streams;
- staging writes;
- HTTP activity;
- helper-process activity;
- normal capability calls;
- real progress reports tied to work.

Long-running host-owned operations must keep the invocation alive automatically. Plugin authors should not need to emit fake heartbeat messages while a download or helper process is genuinely working.

A plugin must not be able to keep a stuck invocation alive forever by sending empty or meaningless “still alive” messages.

If no meaningful progress occurs for the configured idle period, the host may fail and terminate the invocation.

### Absolute fuse

There should also be a much larger host-configurable maximum invocation lifetime.

This is a final safety fuse, not the main hang detector.

The default should be generous enough for unusually large downloads or expensive processing. Core may allow administrators to change it.

No invocation should be immortal.

## Large-channel example

A channel with about 2,000 long videos should not become one multi-day plugin process.

Discovery may commit several batches. As soon as the first batch is committed, Core may begin acquisition work.

Each video's acquisition is independent.

If one video stalls:

- its idle watchdog can fail that acquisition;
- other acquisitions continue;
- already completed work stays complete;
- discovery progress is not lost.

If the host restarts, previously committed discovery and completed acquisition work should not need to be repeated when the relevant continuation/checkpoint data allows resumption.

## Developer tooling

The SDK test harnesses and subprocess development host should make these behaviors testable.

Useful failure tests include:

- discovery that stops making progress;
- download/stream activity that remains alive for a long time;
- helper work that remains active without plugin-side heartbeat spam;
- plugin process crash;
- plugin process exit during an invocation;
- malformed protocol output;
- leaked resources;
- cancellation;
- one failed acquisition among many queued items;
- committed discovery items becoming available before discovery finishes.

The goal is simple: slow work is allowed; silent forever is not.
