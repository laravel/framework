<?php

namespace Illuminate\Redis\Connections;

use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;

class NodeConnection extends Connection implements NodeConnectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        try {
            return $this->connection->getResource();
        } catch (ConnectionException $e) {
            return $this->connection->getResource();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->connection->getParameters();
    }

    /**
     * {@inheritdoc}
     */
    public function addConnectCommand(CommandInterface $command)
    {
        return $this->connection->addConnectCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        try {
            return $this->connection->read();
        } catch (ConnectionException $e) {
            return $this->connection->read();
        }
    }
}
