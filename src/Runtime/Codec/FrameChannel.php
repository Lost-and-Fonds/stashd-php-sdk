<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Runtime\Codec;

use Stashd\PluginSdk\Runtime\ProtocolViolation;
use stdClass;

/**
 * Blocking directional RPC framing; limits count encoded UTF-8 payload bytes only.
 */
final class FrameChannel
{
    /**
     * Input channel exclusively carrying host frames.
     * @var resource
     */
    private $input;

    /**
     * Output channel exclusively carrying plugin frames.
     * @var resource
     */
    private $output;

    /**
     * Local advertised receive limit; bootstrap begins at the universal maximum.
     */
    private int $receiveMaximum = 4096;

    /**
     * Peer advertised receive limit; independent of our own capacity.
     */
    private int $sendMaximum = 4096;

    /**
     * A framing failure permanently disables both directions.
     */
    private bool $usable = true;

    /**
     * Bind already-open binary streams; this class never opens a diagnostics sink.
     *
     * @param resource $input
     * @param resource $output
     */
    public function __construct($input, $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Install independently validated receive advertisements after successful hello.
     */
    public function negotiate(int $receiveMaximum, int $sendMaximum): void
    {
        foreach ([$receiveMaximum, $sendMaximum] as $maximum) {
            if ($maximum < 4096 || $maximum > 4294967295) {
                $this->fail('Receive advertisement must be between 4096 and u32 maximum');
            }
        }

        $this->receiveMaximum = $receiveMaximum;
        $this->sendMaximum = $sendMaximum;
    }

    /**
     * Read one complete frame, returning null only for a clean between-frame EOF.
     */
    public function read(): ?stdClass
    {
        $this->assertUsable();
        $header = $this->readBytes(4, true);

        if ($header === null) {
            return null;
        }

        $decoded = unpack('Nlength', $header);
        $length = $decoded['length'] ?? null;

        if (!is_int($length) || $length < 1 || $length > $this->receiveMaximum) {
            $this->fail('Inbound payload exceeds advertised limit or has zero length');
        }

        $payload = $this->readBytes($length, false);

        try {
            $value = Json::decode($payload ?? '');
        } catch (ProtocolViolation $error) {
            $this->usable = false;

            throw $error;
        }

        if (!$value instanceof stdClass) {
            $this->fail('RPC payload root must be an object');
        }

        return $value;
    }

    /**
     * Transmit a complete frame only after verifying actual encoded size against the peer maximum.
     */
    public function write(stdClass $frame): void
    {
        $this->assertUsable();

        try {
            $payload = Json::encode($frame);
        } catch (ProtocolViolation $error) {
            $this->usable = false;

            throw $error;
        }

        if (strlen($payload) > $this->sendMaximum) {
            $this->fail('Outbound payload exceeds peer receive maximum');
        }

        $bytes = pack('N', strlen($payload)) . $payload;
        $offset = 0;

        while ($offset < strlen($bytes)) {
            $written = fwrite($this->output, substr($bytes, $offset));

            if ($written === false || $written === 0) {
                $this->fail('RPC output closed before complete frame transmission');
            }

            $offset += $written;
        }

        if (!fflush($this->output)) {
            $this->fail('RPC output flush failed');
        }
    }

    /**
     * Invalidate the channel after higher-level contract failure without emitting another frame.
     */
    public function invalidate(): void
    {
        $this->usable = false;
    }

    /**
     * Read exact byte counts; a partial header or payload is never a clean EOF.
     */
    private function readBytes(int $length, bool $allowEof): ?string
    {
        $bytes = '';

        while (($remaining = $length - strlen($bytes)) > 0) {
            $chunk = fread($this->input, $remaining);

            if ($chunk === false || $chunk === '') {
                if ($allowEof && $bytes === '' && feof($this->input)) {
                    return null;
                }

                $this->fail('Truncated RPC header or payload');
            }

            $bytes .= $chunk;
        }

        return $bytes;
    }

    /**
     * Reject any attempt to reuse a channel after an uncertain or invalid exchange.
     */
    private function assertUsable(): void
    {
        if (!$this->usable) {
            throw new ProtocolViolation('RPC channel is unusable');
        }
    }

    /**
     * Atomically poison framing state before reporting the violated invariant.
     */
    private function fail(string $invariant): never
    {
        $this->usable = false;

        throw new ProtocolViolation($invariant);
    }
}
