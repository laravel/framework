<?php

namespace Illuminate\Redis\Connections;

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
        $this->client->executeCommandOnNodes(
            tap(new ServerFlushDatabase)->setArguments(func_get_args())
        );
    }
}
