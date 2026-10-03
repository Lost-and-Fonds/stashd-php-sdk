<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Invocation-local ownership ledger retaining tombstones to reject resource ID reuse.
 */
final class ResourceTable
{
    /**
     * Exact invocation identity; textual handles never establish cross-invocation authority.
     */
    private readonly string $invocation;

    /**
     * Owned handles and permanent invocation-local tombstones.
     * @var array<string, array{type: string, state: string, borrows: array<string, true>}>
     */
    private array $entries = [];

    /**
     * Cleanup permanently closes this ledger, including otherwise live handles.
     */
    private bool $active = true;

    /**
     * Create a fresh ledger for one host-selected invocation.
     */
    public function __construct(string $invocation)
    {
        $this->invocation = $invocation;
    }

    /**
     * Install an incoming owned value only after complete typed frame validation.
     */
    public function accept(string $invocation, string $id, string $type): void
    {
        $this->scope($invocation);

        if ($id === '' || isset($this->entries[$id])) {
            throw new ProtocolViolation('Resource ID is empty or reused within invocation');
        }

        $this->entries[$id] = ['type' => $type, 'state' => 'owned', 'borrows' => []];
    }

    /**
     * Check that a transferred handle can be returned without changing ownership yet.
     */
    public function requireTransferred(string $invocation, string $id, string $type): void
    {
        $this->scope($invocation);
        $entry = $this->entries[$id] ?? null;

        if ($entry === null || $entry['type'] !== $type || $entry['state'] !== 'transferred') {
            throw new ProtocolViolation('Returned resource was not transferred to the host');
        }
    }

    /**
     * Restore an owned resource returned by the host after this invocation transferred it.
     */
    public function returnTransferred(string $invocation, string $id, string $type): void
    {
        $this->requireTransferred($invocation, $id, $type);
        $this->entries[$id]['state'] = 'owned';
    }

    /**
     * Permanently retire a transferred writer discarded by its process.
     */
    public function discardTransferred(string $invocation, string $id, string $type): void
    {
        $this->requireTransferred($invocation, $id, $type);
        $this->entries[$id]['state'] = 'dropped';
    }

    /**
     * Validate type, invocation and ownership before permitting any resource operation.
     */
    public function requireOwned(string $invocation, string $id, string $type): void
    {
        $this->scope($invocation);
        $entry = $this->entries[$id] ?? null;

        if ($entry === null || $entry['type'] !== $type || $entry['state'] !== 'owned') {
            throw new ProtocolViolation('Unknown, stale, transferred, dropped or wrong-type resource');
        }
    }

    /**
     * Establish a call-scoped borrow without consuming the original owner.
     */
    public function borrow(string $invocation, string $id, string $type, string $call): void
    {
        $this->requireOwned($invocation, $id, $type);
        $this->entries[$id]['borrows'][$call] = true;
    }

    /**
     * Validate borrowed access only while its exact call remains outstanding.
     */
    public function requireBorrow(string $invocation, string $id, string $type, string $call): void
    {
        $this->requireOwned($invocation, $id, $type);

        if (!isset($this->entries[$id]['borrows'][$call])) {
            throw new ProtocolViolation('Resource borrow has expired or was never granted');
        }
    }

    /**
     * Expire every nested borrow when its correlated call completes, including error results.
     */
    public function endCall(string $call): void
    {
        foreach ($this->entries as &$entry) {
            unset($entry['borrows'][$call]);
        }
    }

    /**
     * Validate all outgoing owned handles before accepting a frame, including duplicate occurrences.
     * @param list<array{string, string}> $transfers
     */
    public function validateTransfers(string $invocation, array $transfers): void
    {
        $seen = [];

        foreach ($transfers as [$id, $type]) {
            $this->requireOwned($invocation, $id, $type);

            if (isset($seen[$id]) || $this->entries[$id]['borrows'] !== []) {
                throw new ProtocolViolation('Cannot transfer a borrowed or duplicated resource');
            }

            $seen[$id] = true;
        }
    }

    /**
     * Consume outgoing ownership; an accepted call's typed failure does not undo transfer.
     */
    public function transfer(string $invocation, string $id, string $type): void
    {
        $this->retire($invocation, $id, $type, 'transferred');
    }

    /**
     * Invalidate before external cleanup so even cleanup failure cannot revive the handle.
     */
    public function drop(string $invocation, string $id, string $type): void
    {
        $this->retire($invocation, $id, $type, 'dropped');
    }

    /**
     * Unconditionally invalidate every handle and return owned IDs requiring local cleanup.
     * @return list<string>
     */
    public function cleanup(): array
    {
        $owned = [];

        foreach ($this->entries as $id => &$entry) {
            if ($entry['state'] === 'owned') {
                $owned[] = (string) $id;
            }

            $entry['state'] = 'stale';
            $entry['borrows'] = [];
        }

        $this->active = false;

        return $owned;
    }

    /**
     * Reject ownership destruction while a borrowed call is still outstanding.
     */
    private function retire(string $invocation, string $id, string $type, string $state): void
    {
        $this->requireOwned($invocation, $id, $type);

        if ($this->entries[$id]['borrows'] !== []) {
            throw new ProtocolViolation('Cannot consume a resource while borrowed');
        }

        $this->entries[$id]['state'] = $state;
    }

    /**
     * Ensure no operation can resolve another invocation or a cleaned-up ledger.
     */
    private function scope(string $invocation): void
    {
        if (!$this->active || $invocation !== $this->invocation) {
            throw new ProtocolViolation('Resource belongs to an inactive or different invocation');
        }
    }
}
