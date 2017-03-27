<?php

namespace Illuminate\Redis\Connections;

use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\AggregateConnectionInterface;

class AggregateConnection extends Connection implements AggregateConnectionInterface
{
    /**
     * {@inheritdoc}
     */
    public function add(NodeConnectionInterface $connection)
    {
        return $this->connection->add($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeConnectionInterface $connection)
    {
        return $this->connection->remove($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(CommandInterface $command)
    {
        return $this->connection->getConnection($command);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionID)
    {
        return $this->connection->getConnectionById($connectionID);
    }
}
