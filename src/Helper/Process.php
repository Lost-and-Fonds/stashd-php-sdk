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
 * A live host-approved helper, read one event at a time during the plugin call.
 */
final class Process
{
    /**
     * The process belonging to the active plugin call.
     */
    private readonly RemoteHelperProcess $process;

    /**
     * Wrap the live process without exposing protocol details.
     */
    public function __construct(RemoteHelperProcess $process)
    {
        $this->process = $process;
    }

    /**
     * Yield each live byte chunk or activity update, then one terminal outcome and EOF.
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
     * Request cancellation; accepted output and the terminal event remain readable.
     */
    public function cancel(): void
    {
        $this->process->cancel();
    }

    /**
     * Stop observing and drop the process; a running child is terminated by the host.
     */
    public function close(): void
    {
        $this->process->close();
    }
}
