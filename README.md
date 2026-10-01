# Stashd PHP SDK

This branch is the clean-room 0.4.x rewrite of the Stashd PHP authoring SDK for
the frozen `stashd:plugin@0.17.0` contract.

The previous 0.3.x implementation was intentionally removed before the rewrite
so obsolete RPC, lifecycle, media-model, and staging assumptions cannot survive
by accident.

Implementation starts from the language-neutral plugin contract. Repository-wide
engineering rules are in [AGENTS.md](AGENTS.md).

Until the rewrite lands, this branch contains repository scaffolding only.
