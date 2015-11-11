<?php

namespace Illuminate\Queue\Connectors;

use Illuminate\Support\Arr;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Contracts\Database\ConnectionResolver;

class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * @var \Illuminate\Contracts\Database\ConnectionResolver
     */
    protected $connections;

    /**
     * Create a new connector instance.
     *
     * @param  \Illuminate\Contracts\Database\ConnectionResolver  $connections
     * @return void
     */
    public function __construct(ConnectionResolver $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new DatabaseQueue(
            $this->connections->connection(Arr::get($config, 'connection')),
            $config['table'],
            $config['queue'],
            Arr::get($config, 'expire', 60)
        );
    }
}
