<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Predis\Client;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\RetryStrategyInterface;

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
        $config = $this->formatRetry($config);

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

        $options = array_merge($options, $clusterOptions, $clusterSpecificOptions);

        if (isset($options['parameters']) && is_array($options['parameters'])) {
            $options['parameters'] = $this->formatRetry($options['parameters']);
        }

        return new PredisClusterConnection(new Client($servers, $options));
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

    /**
     * Format a scalar retry configuration into a Predis retry instance if applicable.
     *
     * @param  array  $config
     * @return array
     */
    protected function formatRetry(array $config)
    {
        if (! array_key_exists('retry', $config) || ! is_array($config['retry'])) {
            return $config;
        }

        if (! class_exists(Retry::class) || ! interface_exists(RetryStrategyInterface::class)) {
            throw new InvalidArgumentException('Predis retry configuration requires predis/predis 3.4.0 or newer.');
        }

        $strategy = array_key_first($config['retry']);

        $retries = $config['max_retries'] ?? 0;

        if (! is_subclass_of($strategy, RetryStrategyInterface::class)) {
            throw new InvalidArgumentException("Strategy [{$strategy}] is not a valid Predis backoff strategy.");
        }

        $config['retry'] = new Retry(
            new $strategy(...$config['retry'][$strategy]),
            $retries,
        );

        return $config;
    }
}
