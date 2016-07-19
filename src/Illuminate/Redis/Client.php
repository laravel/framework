<?php

namespace Illuminate\Redis;

use Predis\Client as BaseClient;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Aggregate\ClusterInterface;
use Predis\Connection\Aggregate\ReplicationInterface;

class Client extends BaseClient
{
    /**
     * {@inheritdoc}
     */
    protected function createConnection($parameters)
    {
        $connection = parent::createConnection($parameters);

        switch (true) {
            case $connection instanceof ClusterInterface:
                return new Connections\ClusterConnection($connection);
            case $connection instanceof ReplicationInterface:
                return new Connections\ReplicationConnection($connection);
            case $connection instanceof AggregateConnectionInterface:
                return new Connections\AggregateConnection($connection);
            case $connection instanceof CompositeConnectionInterface:
                return new Connections\CompositeConnection($connection);
            case $connection instanceof NodeConnectionInterface:
                return new Connections\NodeConnection($connection);
            default:
                return new Connections\Connection($connection);
        }
    }
}
