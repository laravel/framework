<?php

namespace Illuminate\Redis\Events;

use Illuminate\Redis\Connections\Connection;
use Throwable;

class CommandFailed
{
    /**
     * The Redis connection name.
     */
    public string $connectionName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $command,
        public array $parameters,
        public Throwable $exception,
        public Connection $connection,
    ) {
        $this->connectionName = $connection->getName();
    }
}
