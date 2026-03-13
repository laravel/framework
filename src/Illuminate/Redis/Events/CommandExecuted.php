<?php

namespace Illuminate\Redis\Events;

use Illuminate\Redis\Connections\Connection;

class CommandExecuted
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
        public ?float $time,
        public Connection $connection,
    ) {
        $this->connectionName = $connection->getName();
    }
}
