<?php

namespace Illuminate\Redis\Connectors;

use Predis\Client;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;

class PredisConnector implements Connector
{
    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PredisConnection
     */
    public function connect(array $config, array $options)
    {
        $formattedOptions = array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        );

        return new PredisConnection(new Client($config, $formattedOptions));
    }

    /**
     * Create a new clustered Predis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PredisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        return new PredisClusterConnection(new Client(array_values($config), array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }
}
