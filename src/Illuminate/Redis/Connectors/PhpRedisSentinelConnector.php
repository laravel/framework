<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Redis\Connections\PhpRedisConnection;
use InvalidArgumentException;
use RedisException;
use RedisSentinel;

class PhpRedisSentinelConnector extends PhpRedisConnector
{
    /**
     * The default retry configuration for Sentinel managed connections.
     *
     * @var array
     */
    protected const SENTINEL_DEFAULTS = [
        'command_retries' => 20,
        'backoff_base' => 1000,
        'backoff_cap' => 1000,
    ];

    /**
     * Create a new connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return PhpRedisConnection
     */
    public function connect(array $config, array $options)
    {
        return parent::connect($config + static::SENTINEL_DEFAULTS, $options);
    }

    /**
     * Create the Redis client instance, connected to the master resolved by Sentinel.
     *
     * @param  array  $config
     * @return \Redis
     *
     * @throws \RedisException
     */
    protected function createClient(array $config)
    {
        $master = $this->resolveMaster($config);

        return parent::createClient(array_merge($config, [
            'host' => $master['ip'],
            'port' => (int) $master['port'],
        ]));
    }

    /**
     * Resolve the current master for the configured service from Sentinel.
     *
     * @param  array  $config
     * @return array
     *
     * @throws \RedisException
     */
    protected function resolveMaster(array $config)
    {
        $service = $config['sentinel_service'] ?? 'mymaster';

        $lastException = null;

        foreach ($this->sentinelConfigurations($config) as $sentinelConfig) {
            try {
                $master = $this->connectToSentinel($sentinelConfig)->master($service);
            } catch (RedisException $e) {
                $lastException = $e;

                continue;
            }

            if (is_array($master) && isset($master['ip'], $master['port'])) {
                return $master;
            }

            throw new RedisException("No master found for service [{$service}].");
        }

        throw $lastException ?? new RedisException("No master found for service [{$service}].");
    }

    /**
     * Get the Sentinel connection configurations in the order they should be tried.
     *
     * @param  array  $config
     * @return array
     */
    protected function sentinelConfigurations(array $config)
    {
        if (empty($config['sentinel_hosts'])) {
            return [$config];
        }

        return array_map(fn ($host) => array_merge($config, [
            'sentinel_host' => $host['host'] ?? null,
            'sentinel_port' => (int) ($host['port'] ?? 26379),
        ]), $config['sentinel_hosts']);
    }

    /**
     * Connect to the configured Redis Sentinel instance.
     *
     * @param  array  $config
     * @return \RedisSentinel
     *
     * @throws InvalidArgumentException
     * @throws \RedisException
     */
    protected function connectToSentinel(array $config)
    {
        return new RedisSentinel(...$this->sentinelParameters($config));
    }

    /**
     * Build the RedisSentinel constructor parameters for the installed PhpRedis version.
     *
     * @param  array  $config
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function sentinelParameters(array $config)
    {
        $host = $config['sentinel_host'] ?? null;

        if (! is_string($host) || trim($host) === '') {
            throw new InvalidArgumentException('Redis Sentinel host must be a non-empty string.');
        }

        $port = (int) ($config['sentinel_port'] ?? 26379);
        $timeout = $config['sentinel_timeout'] ?? 0.2;
        $persistent = $config['sentinel_persistent'] ?? null;
        $retryInterval = $config['sentinel_retry_interval'] ?? 0;
        $readTimeout = $config['sentinel_read_timeout'] ?? 0;

        $auth = null;

        if (! empty($config['sentinel_password'])) {
            $auth = isset($config['sentinel_username']) && $config['sentinel_username'] !== '' && is_string($config['sentinel_password'])
                ? [$config['sentinel_username'], $config['sentinel_password']]
                : $config['sentinel_password'];
        }

        if (version_compare($version = $this->phpRedisVersion(), '6.0', '>=')) {
            $options = [
                'host' => $host,
                'port' => $port,
                'connectTimeout' => $timeout,
                'persistent' => $persistent,
                'retryInterval' => $retryInterval,
                'readTimeout' => $readTimeout,
            ];

            if (! is_null($auth)) {
                $options['auth'] = $auth;
            }

            if (version_compare($version, '6.1', '>=') && isset($config['sentinel_ssl'])) {
                $options['ssl'] = $config['sentinel_ssl'];
            }

            return [$options];
        }

        $parameters = [$host, $port, $timeout, $persistent, $retryInterval, $readTimeout];

        if (! is_null($auth)) {
            $parameters[] = $auth;
        }

        return $parameters;
    }

    /**
     * Get the version of the PhpRedis extension.
     *
     * @return string
     */
    protected function phpRedisVersion()
    {
        return phpversion('redis');
    }
}
