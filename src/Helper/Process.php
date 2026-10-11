<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Generator;
use Stashd\PluginSdk\Contract\IoHost\HelperEventOutput;
use Stashd\PluginSdk\Contract\IoHost\HelperEventStdoutActivity;
use Stashd\PluginSdk\Contract\IoHost\HelperEventTerminal;
use Stashd\PluginSdk\Contract\IoHost\HelperOutputStream;
use Stashd\PluginSdk\Contract\IoHost\HelperPollStateEnd;
use Stashd\PluginSdk\Contract\IoHost\HelperPollStateEvent;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalCancelled;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalExited;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalFailed;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalTimedOut;
use Stashd\PluginSdk\Runtime\Codec\AuthorValues;
use Stashd\PluginSdk\Runtime\ProtocolViolation;
use Stashd\PluginSdk\Runtime\Resource\RemoteHelperProcess;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;

/**
 * A running helper process.
 */
final class Process
{
    /**
     * Create a process to read helper output and control its execution.
     *
     * @param RemoteHelperProcess $process Running helper supplied for this call.
     */
    public function __construct(private readonly RemoteHelperProcess $process) {}

    /**
     * Read events as they happen. Iteration yields output or activity, then one final outcome, and stops at EOF.
     * @return Generator<int, Output|StdoutActivity|Exited|Cancelled|TimedOut|Failed|CancelRequested>
     */
    public function events(): Generator
    {
        $notified = false;

        while (true) {
            $poll = $this->poll();

            if ($poll->cancelRequested && !$notified) {
                $notified = true;
                yield new CancelRequested();
            }

            if ($poll->event !== null) {
                yield $poll->event;
            }

            if ($poll->ended) {
                return;
            }
        }
    }

    /**
     * Wait at most the host polling bound and expose invocation cancellation.
     */
    public function poll(int $waitMs = 1000): Poll
    {
        $result = $this->process->nextEvent($waitMs);
        $event = $result->state instanceof HelperPollStateEvent ? $result->state->value : null;
        $public = null;

        if ($event !== null) {
            if ($event instanceof HelperEventOutput) {
                $public = new Output($event->value->channel === HelperOutputStream::Stdout ? OutputStream::Stdout : OutputStream::Stderr, pack('C*', ...$event->value->bytes));
            } elseif ($event instanceof HelperEventStdoutActivity) {
                $public = new StdoutActivity(AuthorValues::authorSize($event->value));
            } elseif ($event instanceof HelperEventTerminal) {
                $terminal = $event->value;

                $public = match (true) {
                    $terminal instanceof HelperTerminalExited => new Exited($terminal->value->code, $terminal->value->output instanceof RemoteStagedWriter ? new Writer($terminal->value->output) : null),
                    $terminal instanceof HelperTerminalCancelled => new Cancelled(),
                    $terminal instanceof HelperTerminalTimedOut => new TimedOut(),
                    $terminal instanceof HelperTerminalFailed => new Failed($terminal->value),
                    default => throw new ProtocolViolation('Unknown helper terminal'),
                };
            }
        }

        return new Poll($result->invocationCancelRequested, $result->state instanceof HelperPollStateEnd, $public);
    }

    /**
     * Ask the host to cancel the helper. You can keep reading events to receive remaining output and the final outcome.
     */
    public function cancel(): void
    {
        $this->process->cancel();
    }

    /**
     * Release the process. If it is still running, the host stops it.
     */
    public function close(): void
    {
        $this->process->close();
    }
}
