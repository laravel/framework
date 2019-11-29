<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\RedisClientClusterConnection;
use Illuminate\Redis\Connections\RedisClientConnection;
use Illuminate\Support\Arr;
use RedisClient\ClientFactory;

class RedisClientConnector implements Connector
{
    /**
     * Create a new clustered RedisClient connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\RedisClientConnection
     */
    public function connect(array $config, array $options)
    {
        $formattedOptions = array_merge([
            'server' => $config['url'] ?? $config['host'].':'.$config['port'],
            'timeout' => 10.0,
        ], $options, Arr::pull($config, 'options', []));

        return new RedisClientConnection(
            ClientFactory::create($formattedOptions),
            Arr::pull($config, 'options.prefix')
        );
    }

    /**
     * Create a new clustered RedisClient connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\RedisClientClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        return new RedisClientClusterConnection(ClientFactory::create(array_merge(
            array_values($config), $options, $clusterOptions, $clusterSpecificOptions
        )));
    }
}
