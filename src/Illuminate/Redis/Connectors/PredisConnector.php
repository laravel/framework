<?php

namespace Illuminate\Redis\Connectors;

use Predis\Client;
use Illuminate\Support\Arr;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;

class PredisConnector
{
    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\PredisConnection
     */
    public function connect(array $config, array $options)
    {
        return new PredisConnection(new Client($config, array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        )));
    }

    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $cluster
     * @param  array  $clusterOptions
     * @param  array  $config
     * @return \Illuminate\Redis\PredisClusterConnection
     */
    public function connectToCluster(array $cluster, array $clusterOptions, array $config)
    {
        $options = Arr::pull($cluster, 'options', []);

        return new PredisClusterConnection(new Client($cluster, array_merge(
            Arr::get($config, 'options', []), $clusterOptions, $options
        )));
    }
}
