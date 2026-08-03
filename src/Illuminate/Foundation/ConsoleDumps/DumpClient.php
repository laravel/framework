<?php

namespace Illuminate\Foundation\ConsoleDumps;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Throwable;

class DumpClient
{
    /**
     * The default dump server host.
     */
    public const DEFAULT_HOST = 'tcp://127.0.0.1:9912';

    /**
     * The maximum number of seconds to wait while connecting to the server.
     */
    protected const CONNECTION_TIMEOUT = 0.05;

    /**
     * The client socket.
     *
     * @var resource|null
     */
    protected $socket;

    /**
     * The variable cloner instance.
     */
    protected VarCloner $cloner;

    /**
     * Create a new dump client instance.
     */
    public function __construct(protected string $host = self::DEFAULT_HOST, ?VarCloner $cloner = null)
    {
        if (! str_contains($this->host, '://')) {
            $this->host = 'tcp://'.$this->host;
        }

        $this->cloner = $cloner ?: new VarCloner;
    }

    /**
     * Send a value to the dump server.
     *
     * @param  array<string, mixed>  $context
     */
    public function dump(mixed $value, array $context = []): void
    {
        try {
            $payload = base64_encode(serialize([
                $this->cloner->cloneVar($value),
                array_filter(['timestamp' => microtime(true), ...$context]),
            ]))."\n";

            $wasConnected = is_resource($this->socket);

            if ($this->write($payload)) {
                return;
            }

            $this->disconnect();

            if ($wasConnected) {
                $this->write($payload);
            }
        } catch (Throwable) {
            $this->disconnect();
        }
    }

    /**
     * Write the given payload to the server.
     */
    protected function write(string $payload): bool
    {
        if (! is_resource($this->socket) && ! $this->connect()) {
            return false;
        }

        $length = strlen($payload);
        $written = 0;

        while ($written < $length) {
            $bytes = @fwrite($this->socket, substr($payload, $written));

            if (! $bytes) {
                return false;
            }

            $written += $bytes;
        }

        return true;
    }

    /**
     * Connect to the dump server.
     */
    protected function connect(): bool
    {
        $socket = @stream_socket_client(
            $this->host,
            $errorCode,
            $errorMessage,
            self::CONNECTION_TIMEOUT,
        );

        if (! is_resource($socket)) {
            return false;
        }

        stream_set_blocking($socket, false);

        $this->socket = $socket;

        return true;
    }

    /**
     * Disconnect from the dump server.
     */
    protected function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    /**
     * Disconnect from the dump server.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
