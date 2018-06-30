<?php

namespace Illuminate\Redis\Connectors;

use Redis;
use RedisCluster;
use Illuminate\Support\Arr;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;

class PhpRedisConnector
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
        return $server['host'].':'.$server['port'].'?'.http_build_query(Arr::only($server, [
            'database', 'password', 'prefix', 'read_timeout',
        ]));
    }

    /**
     * Create the Redis client instance.
     *
     * @param  array  $config
     * @return \Redis
     */
    protected function createClient(array $config)
    {
        return tap(new Redis, function ($client) use ($config) {
            $this->establishConnection($client, $config);

            if (! empty($config['password'])) {
                $client->auth($config['password']);
            }

            if (! empty($config['database'])) {
                $client->select($config['database']);
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
        ($config['persistent'] ?? false)
                ? $this->establishPersistentConnection($client, $config)
                : $this->establishRegularConnection($client, $config);
    }

    /**
     * Establish a persistent connection with the Redis host.
     *
     * @param  \Redis  $client
     * @param  array  $config
     * @return void
     */
    protected function establishPersistentConnection($client, array $config)
    {
        $client->pconnect(
            $config['host'],
            $config['port'],
            Arr::get($config, 'timeout', 0.0),
            Arr::get($config, 'persistent_id', null)
        );
    }

    /**
     * Establish a regular connection with the Redis host.
     *
     * @param  \Redis  $client
     * @param  array  $config
     * @return void
     */
    protected function establishRegularConnection($client, array $config)
    {
        $client->connect(
            $config['host'],
            $config['port'],
            Arr::get($config, 'timeout', 0.0),
            Arr::get($config, 'reserved', null),
            Arr::get($config, 'retry_interval', 0)
        );
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
        return new RedisCluster(
            null,
            array_values($servers),
            $options['timeout'] ?? 0,
            $options['read_timeout'] ?? 0,
            isset($options['persistent']) && $options['persistent']
        );
    }
}
