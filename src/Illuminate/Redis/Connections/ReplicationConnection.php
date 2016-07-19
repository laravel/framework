<?php

namespace Illuminate\Redis\Connections;

use Predis\Connection\ConnectionException;
use Predis\Connection\Aggregate\ReplicationInterface;

class ReplicationConnection extends AggregateConnection implements ReplicationInterface
{
    /**
     * {@inheritdoc}
     */
    public function switchTo($connection);
    {
        try {
            return $this->connection->switchTo($connection);
        } catch (ConnectionException $e) {
            return $this->connection->switchTo($connection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrent()
    {
        return $this->connection->getCurrent();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaster()
    {
        $this->connection->getMaster();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlaves()
    {
        $this->connection->getSlaves();
    }
}
