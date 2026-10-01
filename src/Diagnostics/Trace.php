<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Diagnostics;

use InvalidArgumentException;

/**
 * Safe structured diagnostics: payloads and unrecognized context fields are never serialized.
 */
final class Trace
{
    /**
     * Selected verbosity, fixed for this process trace instance.
     */
    private readonly TraceLevel $level;

    /**
     * Monotonic origin for reconstructing elapsed event timing.
     */
    private readonly int $started;

    /**
     * Explicit diagnostics stream, never the RPC output stream.
     * @var resource
     */
    private $sink;

    /**
     * Bind a safe diagnostics sink; stdout aliases are rejected even at maximum verbosity.
     * @param resource $sink
     */
    public function __construct(TraceLevel $level, $sink)
    {
        $metadata = stream_get_meta_data($sink);
        $uri = $metadata['uri'] ?? '';

        if ($sink === STDOUT || in_array($uri, ['php://stdout', 'php://output', 'php://fd/1'], true)) {
            throw new InvalidArgumentException('Diagnostics must not use RPC stdout');
        }

        $this->level = $level;
        $this->sink = $sink;
        $this->started = hrtime(true);
    }

    /**
     * Check verbosity before constructing expensive context or body metadata.
     */
    public function enabled(TraceLevel $minimum): bool
    {
        return $this->level !== TraceLevel::Off && $this->level->value >= $minimum->value;
    }

    /**
     * Emit only approved metadata fields, replacing every other value with a redaction marker.
     *
     * Event and invariant identifiers must be runtime-owned constants, never peer text or secrets.
     * There is deliberately no raw-payload mode and no diagnostic volume ceiling.
     * @param array<string, scalar|null> $context
     */
    public function emit(TraceLevel $minimum, string $event, array $context = []): void
    {
        if (!$this->enabled($minimum)) {
            return;
        }

        $safe = [];
        $allowed = ['invocation', 'id', 'direction', 'method', 'resource-type', 'resource-id',
            'bytes', 'receive-maximum', 'send-maximum', 'outcome', 'error-class', 'invariant', 'state'];

        foreach ($context as $key => $value) {
            $safe[$key] = in_array($key, $allowed, true) ? $value : '[redacted]';
        }

        $record = ['timestamp' => gmdate('Y-m-d\TH:i:s\Z'), 'elapsed-ns' => hrtime(true) - $this->started,
            'pid' => getmypid(), 'event' => $event, 'context' => $safe];
        $encoded = json_encode($record, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($encoded !== false) {
            fwrite($this->sink, $encoded . "\n");
        }
    }
}
