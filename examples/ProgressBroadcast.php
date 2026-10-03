<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Examples;

use Stashd\PluginSdk\Broadcast\Operation;
use Stashd\PluginSdk\Broadcast\OperationResult;
use Stashd\PluginSdk\BroadcastPlugin;
use Stashd\PluginSdk\Helper\Exited;
use Stashd\PluginSdk\Helper\Output;
use Stashd\PluginSdk\Helper\OutputStream;
use Stashd\PluginSdk\Helper\StdoutActivity;
use Stashd\PluginSdk\Helpers;

/**
 * Demonstrate how a plugin reads real progress from a host-approved helper.
 */
final class ProgressBroadcast implements BroadcastPlugin
{
    /**
     * Provider-reported percentages, not inferred from byte counts.
     * @var list<int>
     */
    public array $percentages = [];

    /**
     * Observed cumulative staged stdout byte counts.
     * @var list<string>
     */
    public array $activity = [];

    /**
     * Bytes read from the finished staged output.
     */
    public string $saved = '';

    /**
     * Run a provider-like helper and parse carriage-return progress from stderr.
     */
    public function operation(Operation $request, Helpers $helpers): OperationResult
    {
        $writer = $helpers->stage('application/octet-stream');
        $process = $helpers->start($request->name, ['--progress'], output: $writer);
        $progress = '';

        foreach ($process->events() as $event) {
            if ($event instanceof Output && $event->stream === OutputStream::Stderr) {
                $progress .= $event->bytes;

                while (($end = strpos($progress, "\r")) !== false) {
                    $line = substr($progress, 0, $end);
                    $progress = substr($progress, $end + 1);

                    if (preg_match('/^download (\d+)%$/', $line, $matches) === 1) {
                        $this->percentages[] = (int) $matches[1];
                    }
                }
            } elseif ($event instanceof StdoutActivity) {
                $this->activity[] = $event->bytes->decimal;
            } elseif ($event instanceof Exited) {
                if ($event->code !== 0 || $event->output === null) {
                    throw new \RuntimeException('Helper did not finish its output');
                }

                foreach ($event->output->finish()->chunks() as $chunk) {
                    $this->saved .= $chunk;
                }
            } else {
                throw new \RuntimeException('Helper did not exit normally');
            }
        }

        $process->close();

        return new OperationResult();
    }
}
