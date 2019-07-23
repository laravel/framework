<?php

namespace Illuminate\Contracts\Redis;

interface Connector
{
    /**
     * Create a new clustered redis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Contracts\Redis\Connection
     */
    public function connect(array $config, array $options);

    /**
     * Create a new clustered redis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Contracts\Redis\Connection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options);
}
