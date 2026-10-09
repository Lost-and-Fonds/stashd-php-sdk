What this repo does: This is the PHP 8.5 SDK for Stashd plugins. It turns the plugin protocol into PHP APIs for discovering and saving inputs, publishing collections, enriching items, exporting collections, and using host-approved helpers. The assumed load is one bounded, synchronous invocation per plugin process, with repeated RPC calls and streaming files; Core owns concurrency, not this SDK.

# Ponytail audit: Ultra

- **Target:** `stashd-php-sdk`, branch `rewrite/0.4.x`.
- **Baseline:** `751379882222c353849c5c23d548d4834dce9407`, including existing modified and untracked working files. This is not an audit of `main` or of the commit alone.
- **Mode:** Ultra. Fix working paths before adding abstractions, compatibility layers, or more features. Generated protocol records and ownership checks are necessary machinery, not bloat merely because they are large.
- **Changes:** This report only. No implementation fixes or commits.

## Must fix

1. **Enrichment cannot use the public capability API** (`src/Runtime/EnrichmentDispatcher.php:44-65`)
   - **What this is:** Plugins return public `Enrichment\Capability` objects to describe the work they support. Both capability discovery and execution translate those descriptions for Core.
   - **Problem:** Both paths send public objects straight into a decoder that expects a JSON record. Returning `[new Capability('thumbnail', '1')]` raises `Expected WIT record`; nonempty advertised capabilities cannot reach normal execution.
   - **Fix:** Convert public capabilities and their nested options and choices into contract values once, then validate and encode them. Add dispatcher tests using the public types; current enrichment tests only validate contract values.
   - **If we skip it:** A plugin implementing the documented API cannot advertise or run useful enrichment. Static analysis currently passes despite this mismatch.

2. **Broadcast cannot yield a valid collection item** (`src/Broadcast/Publish.php:70-130`; `src/Runtime/Codec/AuthorValues.php:43-51`)
   - **What this is:** `Publish::items()` reads batches from Core and yields public items with saved assets and metadata.
   - **Problem:** `typedCall()` already turns wire records into contract `Item` objects. `broadcastItem()` decodes those objects again and raises `Expected WIT record` on the first valid item; the typed error branch also attempts to decode an already-decoded error at `Publish.php:115-116`.
   - **Fix:** Map the decoded contract item directly into the public item and pass the decoded error directly to `HostFailure`. Test a nonempty batch, assets and metadata, EOF, and a typed collection-read failure through the publication path.
   - **If we skip it:** Real publication fails as soon as it reads a selected item, even though empty collections avoid the broken conversion.

3. **Publication reports ignore the host's batch limit** (`src/Broadcast/Publish.php:139-154,181-186`; `tests/BroadcastLifecycleTest.php:66-82`)
   - **What this is:** A publication reports output files and destination metadata, subject to a positive maximum number of records per request.
   - **Problem:** With a maximum of two, reporting three records sends all three at once. Empty lists also produce requests, although empty report batches are invalid; the existing lifecycle test fails because it expects ordered batches of two and one.
   - **Fix:** Make empty lists no-ops and send ordered batches capped by the report limit and the negotiated frame size. Keep the existing test and add byte-size splitting plus a clear failure for a single record too large to send.
   - **If we skip it:** A conforming host rejects ordinary multi-file publications and empty optional reports. The test suite remains red.

4. **Malformed host payloads can leave the invocation usable** (`src/Runtime/Resource/RemoteByteStream.php:69-83`; `src/Runtime/Invocation.php:104-117,153-161`; `src/Broadcast/Publish.php:192-195`)
   - **What this is:** A protocol violation means the host and plugin no longer agree on the message format. The runtime promises to permanently stop the channel and invalidate resources, even if plugin code catches the exception.
   - **Problem:** Several manual result decoders run outside that invalidation boundary. A correlated stream result `{"ok":null,"extra":1}` raises a validation exception, but catching it leaves `requireActive()` successful; manual publication report decoding has the same unguarded structure.
   - **Fix:** Catch decoding `ProtocolViolation` exceptions at every manual host-response boundary and route them through `Invocation::violate()`. Preserve normal typed `HostFailure` handling; test a caught malformed response followed by resource access and another call.
   - **If we skip it:** Author code can continue after invalid host messages, retaining authority that the runtime claims has been revoked. A lifecycle success may conceal a broken protocol exchange.

5. **Rejected helper arguments strand the supplied writer** (`src/Tools.php:40-52`; `src/Helper/Writer.php:31-36`; `src/Runtime/Resource/HelperProcessCall.php:34-48`)
   - **What this is:** Starting a helper hands over optional input and staged output only after a valid request is sent.
   - **Problem:** The public wrapper detaches its writer before argument validation. `start('approved', args: [123], output: $writer)` rejects the argument with zero request bytes sent, but the same writer then says it was already handed to a helper; the stream wrapper is detached before the same preflight too.
   - **Fix:** Validate the complete request before detaching wrappers, and tie wrapper consumption to the actual ownership transfer. Add invalid-argument and invalid-string tests with attached resources; the existing test named for pre-transfer failure only exercises unauthorized credentials, which fail earlier.
   - **If we skip it:** A local validation error makes staged output unavailable to the author even though Core never received it. The writer cannot be reused or explicitly closed through its public wrapper.

6. **An ordinary binary write can terminate the entire invocation** (`src/Helper/Writer.php:42-50`; `src/Runtime/Invocation.php:95-116`)
   - **What this is:** The public writer accepts binary strings and sends bytes through size-limited JSON messages. A frame is one complete message, and its size limit includes its envelope.
   - **Problem:** Each call converts the entire string to an integer list and sends it in one frame. With the valid 4096-byte peer limit, 1024 bytes of `0xff` already exceed the limit before envelope overhead; the resulting exception permanently closes the invocation.
   - **Fix:** Split writes into chunks whose complete encoded requests fit the negotiated peer maximum. Test binary content above one frame with asymmetric limits, exact reconstructed bytes, and successful finish; keep hard frame rejection in the channel.
   - **If we skip it:** Modest file writes fail on allowed host configurations, and unrelated work in that invocation is lost. Large strings also create unnecessary full-size PHP integer arrays.

7. **Helper activity counts are silently narrowed** (`src/Helper/Process.php:38-42`)
   - **What this is:** Staged helper output reports a cumulative unsigned 64-bit byte count. PHP's supported integer range is smaller than the complete protocol range.
   - **Problem:** The public event casts the decimal protocol value directly to `int`. The valid value `18446744073709551615` becomes `9223372036854775807`, silently replacing the real count instead of reporting that it cannot be represented.
   - **Fix:** Use the existing checked `AuthorValues::authorSize()` conversion instead of a direct cast. Test the public event at the largest PHP integer, one above it, and the protocol maximum.
   - **If we skip it:** Large valid counts produce incorrect progress values. This violates the explicit SDK rule to reject unrepresentable integers rather than silently narrow them.

## Should fix

8. **Every completed call scans all old resource IDs** (`src/Runtime/Resource/ResourceTable.php:99-124`; `src/Runtime/Invocation.php:106`)
   - **What this is:** The resource ledger records ownership and retains retired IDs to prevent their reuse. It expires temporary borrows when a call completes.
   - **Problem:** `endCall()` scans every ledger entry, including retired entries, on every response. Creating and closing N resources during one invocation produces roughly N squared visits, even when there is no borrow to expire; this is a structural cost, not a measured production outage.
   - **Fix:** Track outstanding borrowed IDs by call and visit only those when the call ends. Keep retired IDs and ownership checks; test borrow expiry and benchmark resource churn before changing anything broader.
   - **If we skip it:** Resource-heavy bounded jobs spend increasing time scanning old IDs. Small invocations are unaffected enough that this comes after the correctness fixes.

## Checks and evidence

All commands ran on the current rewrite working tree with PHP 8.5.10. Existing failures were not repaired during this report-only audit.

| Check | Result |
| --- | --- |
| `composer test` | **Failed:** 163 passed, 1 failed, 975 assertions. `BroadcastLifecycleTest.php:70` received three records instead of two. |
| `composer test:static` | Passed; no reported errors. |
| `composer check:docs` | **Failed:** `src/Input/Acquisition.php:22`, parameter lacks required description prose. |
| `composer lint` | **Failed:** style check identified seven files. Autoload validation passed. |
| `composer validate --strict` | Passed. |
| `python3 tools/generate-contract.py --check` | Passed; 191 frozen contract declarations verified. |
| `git diff --check` | Passed before creating this report. |

The style failures were in `tests/HelperLifecycleTest.php`, `src/Runtime/InputDispatcher.php`, `src/Runtime/BroadcastDispatcher.php`, `src/Runtime/EnrichmentDispatcher.php`, `src/Broadcast/Publish.php`, `src/Input/Discovery.php`, and `src/Enrichment/Request.php`. These gate failures are reported here, not promoted into taste-based audit findings.

Read-only, in-memory PHP reproductions independently confirmed the malformed-stream active invocation, writer consumption before rejected arguments, oversized binary write invalidation, public capability decode failure, decoded Broadcast item failure, and integer saturation. The existing Broadcast lifecycle test confirms the batching defect. The resource-scan finding is source-derived; no timing claim is made.

## Scope and Ultra judgment

Reviewed the public lifecycle APIs, Input/Broadcast/Enrichment/CollectionExport dispatch and codec paths, helper wrappers, RPC framing and invocation cleanup, resource ownership, relevant tests, README and execution notes, dependencies, generator, documentation tooling, and CI. Generated files were inspected where conversion paths required them, not judged by raw line count.

No new dependency, framework, compatibility layer, or speculative interface is justified by these findings. Reuse existing checked conversion and failure handling; remove duplicate decoding rather than adding another public model. No standalone deletion proposal had enough evidence and benefit to merit a separate finding, so there is no invented line-savings estimate.

The documented missing development host, real OS pipe draining, and backpressure remain release limitations, not newly discovered bugs. The README also states that the working Input routing schema is newer than the published dependency; this is a known packaging limit, not proof that a published package has been tested.

**Verdict:** Not ready for release. Fix public Enrichment and Broadcast conversions and report batching first, then make manual response validation and resource transfer failures safe.

**Not checked:** Real Core integration, installed first-party plugins, real helper child processes and slow-consumer backpressure, fresh dependency installation, remote release artifacts, dependency vulnerability databases, and production load benchmarks. No exhaustive review of every generated record or full protocol conformance proof was performed.
