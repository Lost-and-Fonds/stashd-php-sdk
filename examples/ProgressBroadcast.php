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
 * Example Broadcast plugin that reads live progress from a helper.
 */
final class ProgressBroadcast implements BroadcastPlugin
{
    /**
     * Progress percentages parsed from the helper's own output.
     * @var list<int>
     */
    public array $percentages = [];

    /**
     * Staged stdout byte counts reported while the helper runs.
     * @var list<string>
     */
    public array $activity = [];

    /**
     * Finished helper output.
     */
    public string $saved = '';

    /**
     * Start a helper, parse its carriage-return progress, then read its staged output.
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
