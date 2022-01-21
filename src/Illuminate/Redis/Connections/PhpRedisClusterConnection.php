<?php

namespace Illuminate\Redis\Connections;

class PhpRedisClusterConnection extends PhpRedisConnection
{
    /**
     * Flush the selected Redis database.
     *
     * @param  string|null  $modifier
     * @return mixed
     */
    public function flushdb($modifier = null)
    {
        $async = strtoupper($modifier) === 'ASYNC';

        foreach ($this->client->_masters() as $master) {
            $async
                ? $this->command('rawCommand', [$master, 'flushdb', 'async'])
                : $this->command('flushdb', [$master]);
        }
    }
}
