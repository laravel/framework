<?php

namespace Illuminate\Redis\Connections;

use Countable;
use IteratorAggregate;
use Predis\Connection\Aggregate\ClusterInterface;

class ClusterConnection extends AggregateConnection implements ClusterInterface, IteratorAggregate, Countable
{
    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->connection->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->connection->getIterator();
    }
}
