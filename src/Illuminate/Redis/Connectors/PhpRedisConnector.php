<?php

namespace Illuminate\Redis\Connectors;

use Illuminate\Contracts\Redis\Connector;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis as RedisFacade;
use LogicException;
use Redis;
use RedisCluster;

class PhpRedisConnector implements Connector
{
    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PhpRedisConnection
     */
    public function connect(array $config, array $options)
    {
        return new PhpRedisConnection($this->createClient(array_merge(
            $config, $options, Arr::pull($config, 'options', [])
        )));
    }

    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \Illuminate\Redis\Connections\PhpRedisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));

        return new PhpRedisClusterConnection($this->createRedisClusterInstance(
            array_map([$this, 'buildClusterConnectionString'], $config), $options
        ));
    }

    /**
     * Build a single cluster seed string from array.
     *
     * @param  array  $server
     * @return string
     */
    protected function buildClusterConnectionString(array $server)
    {
        return $server['host'].':'.$server['port'].'?'.Arr::query(Arr::only($server, [
            'database', 'password', 'prefix', 'read_timeout',
        ]));
    }

    /**
     * Create the Redis client instance.
     *
     * @param  array  $config
     * @return \Redis
     *
     * @throws \LogicException
     */
    protected function createClient(array $config)
    {
        return tap(new Redis, function ($client) use ($config) {
            if ($client instanceof RedisFacade) {
                throw new LogicException(
                    'Please remove or rename the Redis facade alias in your "app" configuration file in order to avoid collision with the PHP Redis extension.'
                );
            }

            $this->establishConnection($client, $config);

            if (! empty($config['password'])) {
                $client->auth($config['password']);
            }

            if (isset($config['database'])) {
                $client->select((int) $config['database']);
            }

            if (! empty($config['prefix'])) {
                $client->setOption(Redis::OPT_PREFIX, $config['prefix']);
            }

            if (! empty($config['read_timeout'])) {
                $client->setOption(Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
            }
        });
    }

    /**
     * Establish a connection with the Redis host.
     *
     * @param  \Redis  $client
     * @param  array  $config
     * @return void
     */
    protected function establishConnection($client, array $config)
    {
        $persistent = $config['persistent'] ?? false;

        $parameters = [
            $config['host'],
            $config['port'],
            Arr::get($config, 'timeout', 0.0),
            $persistent ? Arr::get($config, 'persistent_id', null) : null,
            Arr::get($config, 'retry_interval', 0),
        ];

        if (version_compare(phpversion('redis'), '3.1.3', '>=')) {
            $parameters[] = Arr::get($config, 'read_timeout', 0.0);
        }

        $client->{($persistent ? 'pconnect' : 'connect')}(...$parameters);
    }

    /**
     * Create a new redis cluster instance.
     *
     * @param  array  $servers
     * @param  array  $options
     * @return \RedisCluster
     */
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        if (version_compare(phpversion('redis'), '4.3.0', '>=')) {
            return new RedisCluster(
                null,
                array_values($servers),
                $options['timeout'] ?? 0,
                $options['read_timeout'] ?? 0,
                isset($options['persistent']) && $options['persistent'],
                $options['password'] ?? null
            );
        }

        return new RedisCluster(
            null,
            array_values($servers),
            $options['timeout'] ?? 0,
            $options['read_timeout'] ?? 0,
            isset($options['persistent']) && $options['persistent']
        );
    }
}
