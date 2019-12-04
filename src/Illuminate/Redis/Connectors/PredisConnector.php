<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Connections\PredisSentinelConnection;
use Illuminate\Support\Arr;
use Predis\Client;

/**
 * @deprecated Predis is no longer maintained by its original author
 */
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

    /**
     * Create a new sentinel Predis connection.
     *
     * @param  array $config
     * @param  array $sentinelOptions
     * @param  array $options
     * @return \Illuminate\Redis\Connections\PredisSentinelConnection
     */
    public function connectToSentinel(array $config, array $sentinelOptions, array $options)
    {
        $sentinelSpecificOptions = Arr::pull($config, 'options', []);

        return new PredisSentinelConnection(new Client(array_values($config), array_merge(
            $options, $sentinelOptions, $sentinelSpecificOptions
        )));
    }
}
