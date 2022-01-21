<?php

namespace Illuminate\Redis\Connections;

class PhpRedisClusterConnection extends PhpRedisConnection
{
    /**
     * Flush the selected Redis database.
     *
     * @return void
     */
    public function flushdb()
    {
        foreach ($this->client->_masters() as $master) {
            $this->client->flushdb($master);
        }
    }
}
