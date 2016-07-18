<?php

namespace Illuminate\Redis;

use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /**
     * The original connection instance.
     *
     * @var \Predis\Connection\ConnectionInterface
     */
    protected $connection;

    /**
     * Create a new Redis connection instance.
     *
     * @param  \Predis\Connection\ConnectionInterface  $connection
     * @return void
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        try {
            return $this->connection->connect();
        } catch (ConnectionException $e) {
            return $this->connection->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        return $this->connection->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->connection->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function writeRequest(CommandInterface $command)
    {
        return $this->connection->writeRequest($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(CommandInterface $command)
    {
        return $this->connection->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        return $this->connection->executeCommand($command);
    }
}
