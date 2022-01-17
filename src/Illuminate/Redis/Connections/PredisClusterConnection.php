<?php

namespace Illuminate\Redis\Connections;

use Predis\Command\ServerFlushDatabase;

class PredisClusterConnection extends PredisConnection
{
    /**
     * Flush the selected Redis database.
     *
     * @return void
     */
    public function flushdb()
    {
        foreach ($this->getConnection()->getIterator() as $node) {
            $node->executeCommand(new ServerFlushDatabase);
        }
    }
}
