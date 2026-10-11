# Fresh Ultra audit remediation

Implementation baseline: `rewrite/0.4.x`, HEAD `32ebec048fc7cda436434fdf6842ee3a32948cca`, including the pre-existing dirty working tree. No commits or workspace submodule pointer updates were made.

Authoritative contract: `stashd:plugin@0.19.0`, plugin-api commit `585e4a377935d9ec46b38572f65e6ec72983860b` (published tag `v0.19.0`). The tag still declares Composer version 0.2.2. This SDK explicitly describes the verified archive as package 0.19.0; downstream root applications must configure that repository until upstream corrects release metadata. No sibling checkout is a production dependency.

## Finding mapping

| Finding | Implementation | Behavioral evidence |
| --- | --- | --- |
| 1 | Authoritative schemas/vectors, identity guards, installed reference and artifact hashes; bounded polls | Contract check, helper tests, cancellation regression tests |
| 2 | One public capability-to-contract conversion | Public capability discovery and derived artifact execution |
| 3 | Map decoded collection items directly; retain decoded typed errors | Nonempty public publication with saved asset reads |
| 4 | Consistent request `tools()` access | Resolution/discovery, staged acquisition and enrichment tests |
| 5 | Count and complete encoded-frame report batching; empty no-op | Existing report lifecycle and byte-splitting regression |
| 6 | Guard manual decoding and require active lifecycle response | Caught malformed stream revokes invocation authority |
| 7 | Pass decoded staging errors directly | Denied and failed staging responses remain HostFailure |
| 8 | Consume public stream/writer wrappers at successful-frame transfer | Invalid arguments preserve both wrappers; accepted transfers prohibit reuse |
| 9 | Frame-fitting binary writes using the accepted prepared payload | Exact multi-frame binary reconstruction |
| 10 | Reject typed discovery failure after acknowledged completion | Public discovery terminal-failure regression |
| 11 | Require collection EOF and reject failed/empty collection prefixes | Nonempty publication plus premature-success rejection |
| 12 | Checked public activity conversion | Wide activity count explicitly rejected |
| 13 | Encode native s64 as exact decimal text | Zero and both signed boundaries |
| 14 | Index active borrows by call; retain tombstones | Resource ownership tests and churn measurements |

A public `CancelRequested` event is yielded once per `events()` iteration, before any accompanying output/terminal event or end. The iterator never silently hides a cancellation first observed during a pending poll. It does not automatically cancel helpers. Explicit polls after end continue querying the sticky invocation flag.

Successful writer finish leaves the handle owned; explicit close releases only the handle, not the artifact. Canonical writer poisoning, artifact registration/adoption, child reaping, output spooling, fixed collection membership, and preserved-asset grants remain Core responsibilities.

## Verification

- `composer test`: 181 passed, 1069 assertions, including a real PHP plugin subprocess export.
- Static analysis, declaration documentation checks, lint, strict Composer validation, and whitespace checks pass.
- `composer check:contract`: 196 generated declarations verified against installed upstream artifacts and pinned hashes.
- Disposable stale-snapshot test fails as required.
- Fresh production dependency installation matches the authoritative schema without sibling paths.
- Upstream `bin/verify-contract` passes semantic vectors and mutation sensitivity checks.
- Resource churn: 1,000/10,000/50,000 retired resources completed in 0.498/5.179/29.376 ms on this machine. These are observations, not CI timing thresholds.

Local development dependency updating required ignoring the missing iconv extension; production installation succeeded without that exception. CI installs iconv normally.

## Downstream release requirements

Run real Core tests for pipe draining under slow consumption, bounded cancellation wakeups, first-terminal-condition wins, helper termination/reaping, atomic canonical artifact adoption, preserved asset authority, and publication EOF acceptance. SDK fake-host tests do not prove those host guarantees. Provider plugins and a production development host were not rewritten. The previous AUDIT.md remains historical evidence, not a current release verdict.
