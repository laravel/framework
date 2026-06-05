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
use Predis\Retry\Strategy\EqualBackoff;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\Retry\Strategy\NoBackoff;

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

        $config = $this->formatRetry($config);

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
            if (is_array($server)) {
                $server = $this->formatHost($server);
                $server = $this->formatRetry($server);
            }

            return $server;
        }, array_values($config));

        return new PredisClusterConnection(new Client($servers, array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }

    /**
     * Format the retry parameter.
     *
     * @param  array  $config
     * @return array
     */
    protected function formatRetry(array $config)
    {
        if (isset($config['retry']) && is_array($config['retry']) && class_exists(Retry::class)) {
            $retry = $config['retry'];
            $retries = $retry['retries'] ?? 0;
            $strategy = $retry['strategy'] ?? 'exponential';

            $backoff = match ($strategy) {
                'equal' => new EqualBackoff($retry['backoff'] ?? 0),
                'no' => new NoBackoff,
                'exponential' => new ExponentialBackoff(
                    $retry['base'] ?? 250000,
                    $retry['cap'] ?? 2000000,
                    $retry['with_jitter'] ?? false
                ),
                default => is_string($strategy) && class_exists($strategy)
                    ? new $strategy(...($retry['parameters'] ?? []))
                    : $strategy,
            };

            $config['retry'] = new Retry($backoff, $retries);
        }

        return $config;
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
