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

        return new PredisClusterConnection(new Client($servers, array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }

    /**
     * Format scalar retry configuration into a Predis retry instance.
     *
     * @param  array  $config
     * @return array
     */
    protected function formatRetry(array $config)
    {
        if (! array_key_exists('retry', $config) || ! is_array($config['retry'])) {
            return $config;
        }

        if (! class_exists(Retry::class) ||
            ! class_exists(ExponentialBackoff::class) ||
            ! class_exists(EqualBackoff::class) ||
            ! class_exists(NoBackoff::class)) {
            throw new InvalidArgumentException('Predis retry configuration requires predis/predis 3.4.0 or newer.');
        }

        $retry = $config['retry'];
        $retries = Arr::pull($retry, 'max_retries', Arr::pull($retry, 'retries', 0));
        $algorithm = Arr::pull($retry, 'backoff_algorithm', 'exponential');
        $base = Arr::pull($retry, 'backoff_base', ExponentialBackoff::DEFAULT_BASE);
        $cap = Arr::pull($retry, 'backoff_cap', ExponentialBackoff::DEFAULT_CAP);

        $config['retry'] = new Retry(
            $this->parseBackoffStrategy($algorithm, (int) $base, (int) $cap),
            (int) $retries
        );

        return $config;
    }

    /**
     * Parse a Predis backoff strategy.
     *
     * @param  mixed  $algorithm
     * @param  int  $base
     * @param  int  $cap
     * @return \Predis\Retry\Strategy\RetryStrategyInterface
     */
    protected function parseBackoffStrategy($algorithm, int $base, int $cap)
    {
        if (! is_string($algorithm)) {
            throw new InvalidArgumentException('Predis backoff algorithm must be a string.');
        }

        return match (Str::snake($algorithm)) {
            'default', 'exponential' => new ExponentialBackoff($base, $cap),
            'constant', 'equal', 'equal_backoff' => new EqualBackoff($base),
            'none', 'no_backoff' => new NoBackoff,
            default => throw new InvalidArgumentException("Algorithm [{$algorithm}] is not a valid Predis backoff algorithm."),
        };
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
