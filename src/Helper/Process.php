<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Helper;

use Stashd\PluginSdk\Contract\IoHost\HelperEventOutput;
use Stashd\PluginSdk\Contract\IoHost\HelperEventStdoutActivity;
use Stashd\PluginSdk\Contract\IoHost\HelperEventTerminal;
use Stashd\PluginSdk\Contract\IoHost\HelperOutputStream;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalCancelled;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalExited;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalFailed;
use Stashd\PluginSdk\Contract\IoHost\HelperTerminalTimedOut;
use Stashd\PluginSdk\Runtime\Resource\RemoteHelperProcess;
use Stashd\PluginSdk\Runtime\Resource\RemoteStagedWriter;
use Stashd\PluginSdk\Runtime\ProtocolViolation;

/**
 * A running helper process.
 */
final class Process
{
    /**
     * The underlying process for this plugin call.
     */
    private readonly RemoteHelperProcess $process;

    /**
     * Create the public wrapper around a running helper.
     */
    public function __construct(RemoteHelperProcess $process)
    {
        $this->process = $process;
    }

    /**
     * Read events as they happen. Iteration yields output or activity, then one final outcome, and stops at EOF.
     * @return \Generator<int, Output|StdoutActivity|Exited|Cancelled|TimedOut|Failed>
     */
    public function events(): \Generator
    {
        while (($event = $this->process->nextEvent()) !== null) {
            if ($event instanceof HelperEventOutput) {
                yield new Output($event->value->channel === HelperOutputStream::Stdout ? OutputStream::Stdout : OutputStream::Stderr, pack('C*', ...$event->value->bytes));
            } elseif ($event instanceof HelperEventStdoutActivity) {
                yield new StdoutActivity($event->value);
            } elseif ($event instanceof HelperEventTerminal) {
                $terminal = $event->value;

                yield match (true) {
                    $terminal instanceof HelperTerminalExited => new Exited($terminal->value->code, $terminal->value->output instanceof RemoteStagedWriter ? new Writer($terminal->value->output) : null),
                    $terminal instanceof HelperTerminalCancelled => new Cancelled(),
                    $terminal instanceof HelperTerminalTimedOut => new TimedOut(),
                    $terminal instanceof HelperTerminalFailed => new Failed($terminal->value),
                    default => throw new ProtocolViolation('Unknown helper terminal'),
                };
            }
        }
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
