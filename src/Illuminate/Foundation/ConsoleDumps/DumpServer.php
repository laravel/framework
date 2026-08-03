<?php

namespace Illuminate\Foundation\ConsoleDumps;

use Psr\Log\LoggerInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Server\DumpServer as SymfonyDumpServer;

class DumpServer
{
    /**
     * The underlying Symfony dump server instance.
     */
    protected SymfonyDumpServer $server;

    /**
     * Create a new dump server instance.
     */
    public function __construct(string $host = DumpClient::DEFAULT_HOST, ?LoggerInterface $logger = null)
    {
        $this->server = new SymfonyDumpServer($host, $logger);
    }

    /**
     * Start the dump server.
     */
    public function start(): void
    {
        $this->server->start();
    }

    /**
     * Listen for incoming dumps.
     *
     * @param  callable(Data, array<string, mixed>, int): void  $callback
     */
    public function listen(callable $callback): void
    {
        $this->server->listen($callback);
    }

    /**
     * Get the dump server host.
     */
    public function getHost(): string
    {
        return $this->server->getHost();
    }
}
