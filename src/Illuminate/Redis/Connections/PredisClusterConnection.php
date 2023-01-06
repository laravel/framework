<?php

namespace Illuminate\Redis\Connections;

use Predis\Command\Redis\FLUSHDB;
use Predis\Command\ServerFlushDatabase;

class PredisClusterConnection extends PredisConnection
{
    /**
     * Flush the selected Redis database on all cluster nodes.
     *
     * @return void
     */
    public function flushdb()
    {
        $command = class_exists(ServerFlushDatabase::class)
            ? ServerFlushDatabase::class
            : FLUSHDB::class;

        $this->client->executeCommandOnNodes(
            tap($command)->setArguments(func_get_args())
        );
    }
}
