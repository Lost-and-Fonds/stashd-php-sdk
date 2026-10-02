<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Resource;

use Stashd\PluginSdk\Contract\IoHost\HelperEvent;
use Stashd\PluginSdk\Contract\IoHost\HelperEventOutput;
use Stashd\PluginSdk\Contract\IoHost\HelperEventStdoutActivity;
use Stashd\PluginSdk\Contract\IoHost\HelperEventTerminal;
use Stashd\PluginSdk\Contract\IoHost\HelperOutputStream;
use Stashd\PluginSdk\Contract\IoHost\HelperProcess;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalCancelled;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalExited;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalFailed;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalTimedOut;
use Stashd\PluginSdk\Diagnostics\TraceLevel;
use Stashd\PluginSdk\Runtime\Codec\ResourceValueCodec;
use Stashd\PluginSdk\Runtime\Invocation;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * Exact invocation-scoped helper resource, not a public provider-facing process API.
 */
final class RemoteHelperProcess implements HelperProcess, OwnedResource
{
    /**
     * Canonical process resource identity.
     */
    private readonly string $id;

    /**
     * Invocation responsible for process resource release and cleanup.
     */
    private readonly Invocation $invocation;

    /**
     * True only when stdout was transferred into a staged writer.
     */
    private readonly bool $staged;

    /**
     * Last accepted cumulative stdout activity count, represented without u64 precision loss.
     */
    private string $activity = '0';

    /**
     * Terminal and EOF state are independent of process resource ownership.
     */
    private bool $terminal = false;

    /**
     * True after the first canonical post-terminal EOF result.
     */
    private bool $eof = false;

    /**
     * Writer transferred into this process, if stdout was staged.
     */
    private readonly ?string $writerId;

    /**
     * Bind a host-owned process already installed in this invocation's ledger.
     */
    public function __construct(Invocation $invocation, string $id, ?string $writerId)
    {
        $this->invocation = $invocation;
        $this->id = $id;
        $this->staged = $writerId !== null;
        $this->writerId = $writerId;
        $invocation->resources->requireOwned($invocation->id, $id, 'stashd:plugin/io-host.helper-process');
        $invocation->trace(TraceLevel::Ludicrous, 'helper.process', ['resource-id' => $id,
            'resource-type' => 'stashd:plugin/io-host.helper-process']);
    }

    /**
     * Verify the process proxy belongs to the same invocation and WIT resource type.
     */
    public function resourceId(ResourceTable $table, string $invocation, string $type): string
    {
        if ($this->invocation->resources !== $table || $this->invocation->id !== $invocation || $type !== 'stashd:plugin/io-host.helper-process') {
            throw new ProtocolViolation('Helper process proxy belongs to another invocation or type');
        }

        $table->requireOwned($invocation, $this->id, $type);

        return $this->id;
    }

    /**
     * Wait for the next accepted event, then a terminal event, then sticky EOF.
     */
    public function nextEvent(): ?HelperEvent
    {
        $this->resourceId($this->invocation->resources, $this->invocation->id, 'stashd:plugin/io-host.helper-process');

        if ($this->eof) {
            return null;
        }

        $event = $this->invocation->typedCall('stashd:plugin/io-host.helper-process.next-event', [
            'self' => $this,
        ], ['self' => ['kind' => 'borrow', 'value' => ['kind' => 'named', 'name' => 'helper-process']]], [
            'kind' => 'option', 'value' => ['kind' => 'named', 'name' => 'helper-event'],
        ], 'io-host', $this->writerId);

        if ($event === null) {
            if (!$this->terminal) {
                $this->invocation->violate('Helper process reached EOF before terminal');
            }

            $this->eof = true;

            return null;
        }

        if (!$event instanceof HelperEvent || $this->terminal) {
            $this->invocation->violate('Helper process emitted invalid event after terminal');
        }

        if ($event instanceof HelperEventOutput) {
            $output = $event->value;

            if ($output->bytes === [] || ($this->staged && $output->channel === HelperOutputStream::Stdout)) {
                $this->invocation->violate('Helper output is empty or duplicates staged stdout');
            }

            $this->invocation->trace(TraceLevel::Ludicrous, 'helper.output', [
                'resource-id' => $this->id, 'state' => $output->channel->value, 'bytes' => count($output->bytes),
            ]);
        } elseif ($event instanceof HelperEventStdoutActivity) {
            $count = $event->value->decimal;

            if (!$this->staged || self::lessThan($count, $this->activity)) {
                $this->invocation->violate('Helper stdout activity is invalid for this output mode');
            }

            $this->activity = $count;
            $this->invocation->trace(TraceLevel::Ludicrous, 'helper.stdout-activity', [
                'resource-id' => $this->id, 'state' => $count,
            ]);
        } elseif ($event instanceof HelperEventTerminal) {
            $this->terminal = true;
            $terminal = $event->value;
            $kind = match (true) {
                $terminal instanceof HelperTerminalExited => 'exited',
                $terminal instanceof HelperTerminalCancelled => 'cancelled',
                $terminal instanceof HelperTerminalTimedOut => 'timed-out',
                $terminal instanceof HelperTerminalFailed => 'failed',
                default => $this->invocation->violate('Unknown helper terminal outcome'),
            };

            if ($terminal instanceof HelperTerminalExited && !$this->staged && $terminal->value->output !== null) {
                $this->invocation->violate('Unstaged helper returned a writer');
            }

            if ($this->writerId !== null) {
                $this->invocation->trace(TraceLevel::Ludicrous, 'helper.writer', [
                    'resource-id' => $this->writerId,
                    'outcome' => $terminal instanceof HelperTerminalExited && $terminal->value->output !== null ? 'returned' : 'discarded',
                ]);
            }

            $this->invocation->trace(TraceLevel::Ludicrous, 'helper.terminal', [
                'resource-id' => $this->id, 'outcome' => $kind,
                'state' => $terminal instanceof HelperTerminalExited ? (string) $terminal->value->code : null,
            ]);
        }

        return $event;
    }

    /**
     * Request canonical idempotent cancellation without fabricating a terminal result locally.
     */
    public function cancel(): null
    {
        $this->resourceId($this->invocation->resources, $this->invocation->id, 'stashd:plugin/io-host.helper-process');
        $this->invocation->trace(TraceLevel::Ludicrous, 'helper.cancel', ['resource-id' => $this->id]);
        $result = $this->invocation->call('stashd:plugin/io-host.helper-process.cancel', (object) [
            'self' => ResourceValueCodec::handle('stashd:plugin/io-host.helper-process', $this->id),
        ]);

        if ($result !== null) {
            $this->invocation->violate('Helper cancellation must return unit');
        }

        return null;
    }

    /**
     * Drop the owned process; the host must terminate and reap any still-running child.
     */
    public function close(): void
    {
        $this->invocation->drop($this->id, 'stashd:plugin/io-host.helper-process');

        if (!$this->terminal && $this->writerId !== null) {
            $this->invocation->trace(TraceLevel::Ludicrous, 'helper.writer', [
                'resource-id' => $this->writerId, 'outcome' => 'discarded',
            ]);
        }
    }

    /**
     * Compare canonical unsigned decimal counts without converting beyond PHP integer range.
     */
    private static function lessThan(string $left, string $right): bool
    {
        return strlen($left) < strlen($right) || (strlen($left) === strlen($right) && strcmp($left, $right) < 0);
    }
}
