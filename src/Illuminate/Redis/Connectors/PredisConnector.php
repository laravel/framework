<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Predis\Client;

class PredisConnector implements Connector
{
    /**
     * Create a new connection.
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

        if (isset($config['prefix'])) {
            $formattedOptions['prefix'] = $config['prefix'];
        }

        $config = $this->formatHost($config);

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

        if (isset($config['prefix'])) {
            $clusterSpecificOptions['prefix'] = $config['prefix'];
        }

        $servers = array_map(function ($server) {
            return is_array($server) ? $this->formatHost($server) : $server;
        }, array_values($config));

        return new PredisClusterConnection(new Client($servers, array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }

    /**
     * Format the host using the scheme if available.
     *
     * @param  array  $config
     * @return array
     */
    protected function formatHost(array $config)
    {
        $host = $config['host'] ?? null;

        if (! is_string($host) || $host === '') {
            return $config;
        }

        $hostScheme = parse_url($host, PHP_URL_SCHEME);

        if (! is_string($hostScheme)) {
            return $config;
        }

        if (isset($config['scheme']) && strcasecmp($hostScheme, $config['scheme']) !== 0) {
            throw new InvalidArgumentException('The scheme configured in the Redis host option must match the scheme option.');
        }

        $config['scheme'] = $config['scheme'] ?? $hostScheme;
        $config['host'] = Str::after($host, "{$hostScheme}://");

        return $config;
    }
}
