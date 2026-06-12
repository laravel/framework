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

        if (isset($config['host']) && str_starts_with($config['host'], 'tls://')) {
            $config['scheme'] = 'tls';
            $config['host'] = Str::after($config['host'], 'tls://');
        }

        if ($retry = $this->buildRetry($config)) {
            $config['retry'] = $retry;
        }

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

        return new PredisClusterConnection(new Client(array_values($config), array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }

    /**
     * Build a Predis Retry instance from scalar configuration values.
     *
     * This allows retry/backoff configuration to be stored as scalar values
     * in the config, making it compatible with config:cache serialization.
     *
     * @param  array  $config
     * @return \Predis\Retry\Retry|null
     */
    protected function buildRetry(array $config)
    {
        $retries = $config['max_retries'] ?? null;
        $algorithm = $config['backoff_algorithm'] ?? null;
        $base = $config['backoff_base'] ?? null;
        $cap = $config['backoff_cap'] ?? null;

        if ($retries === null && $algorithm === null) {
            return null;
        }

        $retries = (int) ($retries ?? 0);
        $base = (int) ($base ?? 100_000);
        $cap = (int) ($cap ?? 0);
        $algorithm = $algorithm ?? 'exponential';

        return new Retry($this->buildBackoffStrategy($algorithm, $base, $cap), $retries);
    }

    /**
     * Build a backoff strategy from the given algorithm name and parameters.
     *
     * @param  string  $algorithm
     * @param  int  $base  Base delay in microseconds.
     * @param  int  $cap  Maximum delay in microseconds.
     * @return \Predis\Retry\Strategy\RetryStrategyInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function buildBackoffStrategy(string $algorithm, int $base, int $cap)
    {
        return match ($algorithm) {
            'exponential' => new ExponentialBackoff($base, $cap),
            'exponential_jitter' => new ExponentialBackoff($base, $cap, true),
            'equal' => new EqualBackoff($base),
            'none' => new NoBackoff,
            default => throw new InvalidArgumentException("Algorithm [{$algorithm}] is not a valid Predis backoff algorithm."),
        };
    }
}
