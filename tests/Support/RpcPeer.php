<?php

declare(strict_types=1);

namespace Stashd\PluginSdk\Tests\Support;

use RuntimeException;

final class RpcPeer
{
    /** @var resource */
    private $process;

    /** @var array{0: resource, 1: resource, 2: resource} */
    private array $pipes;

    /** @var list<array<string, mixed>> */
    private array $hostCalls = [];

    private int $nextId = 1;

    /** @param callable(array<string, mixed>): array<string, mixed> $host */
    private function __construct($process, array $pipes, private $host)
    {
        $this->process = $process;
        $this->pipes = $pipes;
        stream_set_timeout($this->pipes[1], 5);
    }

    /** @param callable(array<string, mixed>): array<string, mixed>|null $host */
    public static function start(string $world, ?callable $host = null): self
    {
        $command = [PHP_BINARY, dirname(__DIR__) . '/Fixtures/plugin-server.php', $world];
        $pipes = [];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2));

        if (! is_resource($process)) {
            throw new RuntimeException('could not start SDK fixture process');
        }

        $peer = new self($process, $pipes, $host ?? static fn(array $message): array => []);
        $hello = $peer->readMessage();

        if (($hello['kind'] ?? null) !== 'request' || ($hello['id'] ?? null) !== 'sdk-hello' || ($hello['method'] ?? null) !== 'hello') {
            throw new RuntimeException('fixture did not send the SDK handshake');
        }

        $peer->writeMessage(['protocol' => 1, 'id' => 'sdk-hello', 'kind' => 'response', 'result' => ['protocol' => 1, 'min' => 1, 'max' => 1]]);

        return $peer;
    }

    /** @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params): array
    {
        $id = 'test-' . $this->nextId++;
        $this->writeMessage(['protocol' => 1, 'id' => $id, 'kind' => 'request', 'method' => $method, 'params' => $params]);

        while (true) {
            $message = $this->readMessage();

            if (($message['kind'] ?? null) === 'request') {
                $this->hostCalls[] = $message;
                $result = ($this->host)($message);
                $response = ['protocol' => 1, 'id' => $message['id'] ?? '', 'kind' => 'response'];

                if (array_key_exists('__rpc_error', $result)) {
                    $response['error'] = $result['__rpc_error'];
                } else {
                    $response['result'] = $result;
                }
                $this->writeMessage($response);

                continue;
            }

            if (($message['kind'] ?? null) !== 'response' || ($message['id'] ?? null) !== $id) {
                throw new RuntimeException('SDK returned an unmatched RPC frame');
            }

            return $message;
        }
    }

    /** @return list<array<string, mixed>> */
    public function hostCalls(): array
    {
        return $this->hostCalls;
    }

    public function close(): int
    {
        if (! is_resource($this->process)) {
            return 0;
        }

        fclose($this->pipes[0]);
        fclose($this->pipes[1]);
        fclose($this->pipes[2]);
        $exitCode = proc_close($this->process);
        $this->process = null;

        return $exitCode;
    }

    public function __destruct()
    {
        if (is_resource($this->process)) {
            $this->close();
        }
    }

    /** @param array<string, mixed> $message */
    private function writeMessage(array $message): void
    {
        $payload = json_encode($message, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $frame = pack('N', strlen($payload)) . $payload;
        $offset = 0;

        while ($offset < strlen($frame)) {
            $written = fwrite($this->pipes[0], substr($frame, $offset));

            if (! is_int($written) || $written <= 0) {
                throw new RuntimeException('could not write fixture RPC frame');
            }
            $offset += $written;
        }
        fflush($this->pipes[0]);
    }

    /** @return array<string, mixed> */
    private function readMessage(): array
    {
        $header = $this->readBytes(4);
        $length = unpack('Nlength', $header)['length'] ?? null;

        if (! is_int($length) || $length < 2) {
            throw new RuntimeException('fixture returned an invalid RPC frame length');
        }

        $decoded = json_decode($this->readBytes($length), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('fixture returned a non-object RPC message');
        }

        return $decoded;
    }

    private function readBytes(int $length): string
    {
        $result = '';

        while (strlen($result) < $length) {
            $chunk = fread($this->pipes[1], $length - strlen($result));

            if (! is_string($chunk) || $chunk === '') {
                $meta = stream_get_meta_data($this->pipes[1]);

                throw new RuntimeException(($meta['timed_out'] ?? false) ? 'timed out reading SDK RPC frame' : 'SDK RPC frame was truncated');
            }
            $result .= $chunk;
        }

        return $result;
    }
}
