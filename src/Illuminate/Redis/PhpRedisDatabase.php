<?php

namespace Illuminate\Redis;

use Redis;
use Closure;
use RedisCluster;
use Illuminate\Support\Arr;

class PhpRedisDatabase extends Database
{
    /**
     * The host address of the database.
     *
     * @var array
     */
    protected $clients;

    /**
     * Create a new Redis connection instance.
     *
     * @param array $servers
     */
    public function __construct(array $servers = [])
    {
        $cluster = Arr::pull($servers, 'cluster');
        $clusters = (array) Arr::pull($servers, 'clusters');

        $options = (array) Arr::pull($servers, 'options');

        if ($cluster) {
            $this->clients = $this->createAggregateClient($servers, $options);
        } else {
            $this->clients = $this->createSingleClients($servers, $options);
        }

        $this->createClusters($clusters, $options);
    }

    /**
     * Create multiple clusters (aggregate clients).
     *
     * @param array $clusters
     * @param array $options
     */
    protected function createClusters(array $clusters, array $options = [])
    {
        // Merge general options with general cluster options
        $options = array_merge($options, (array) Arr::pull($clusters, 'options'));

        foreach ($clusters as $connection => $servers) {
            // Merge specific cluster options with general options
            $options = array_merge($options, (array) Arr::pull($servers, 'options'));

            $this->clients += $this->createAggregateClient($servers, $options, $connection);
        }
    }

    /**
     * Create a new aggregate client supporting sharding.
     *
     * @param array  $servers
     * @param array  $options
     * @param string $connection
     *
     * @return array
     */
    protected function createAggregateClient(array $servers, array $options = [], $connection = 'default')
    {
        $servers = array_map([$this, 'buildClusterSeed'], $servers);

        return [$connection => $this->createRedisClusterInstance($servers, $options)];
    }

    /**
     * Create an array of single connection clients.
     *
     * @param array $servers
     * @param array $options
     *
     * @return array
     */
    protected function createSingleClients(array $servers, array $options = [])
    {
        $clients = [];

        foreach ($servers as $key => $server) {
            $client = $this->createRedisInstance($server, $options);
            $clients[$key] = $client;
        }

        return $clients;
    }

    /**
     * Build a single cluster seed string from array.
     *
     * @param array $server
     *
     * @return string
     */
    protected function buildClusterSeed(array $server)
    {
        $parameters = Arr::only($server, [
            'database', 'password', 'prefix', 'read_timeout',
        ]);

        return $server['host'].':'.$server['port'].'?'.http_build_query($parameters);
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param array|string $channels
     * @param \Closure     $callback
     * @param string       $connection
     * @param string       $method
     */
    public function subscribe($channels, Closure $callback, $connection = null, $method = 'subscribe')
    {
        call_user_func_array([$this->connection($connection), $method], (array) $channels, $callback);
    }

    /**
     * Subscribe to a set of given channels with wildcards.
     *
     * @param array|string $channels
     * @param \Closure     $callback
     * @param string       $connection
     */
    public function psubscribe($channels, Closure $callback, $connection = null)
    {
        $this->subscribe($channels, $callback, $connection, __FUNCTION__);
    }

    /**
     * Create a new redis cluster instance.
     *
     * @param array $servers
     * @param array $options
     *
     * @return RedisCluster
     */
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        return new RedisCluster(
            null,
            array_values($servers),
            Arr::get($options, 'timeout', 0),
            Arr::get($options, 'read_timeout', 0),
            isset($options['persistent']) && $options['persistent']
        );
    }

    /**
     * Create a new redis instance.
     *
     * @param array $server
     * @param array $options
     *
     * @return Redis
     */
    protected function createRedisInstance(array $server, array $options)
    {
        $client = new Redis();

        $timeout = empty($server['timeout']) ? 0 : $server['timeout'];

        if (isset($server['persistent']) && $server['persistent']) {
            $client->pconnect($server['host'], $server['port'], $timeout);
        } else {
            $client->connect($server['host'], $server['port'], $timeout);
        }

        if (!empty($server['prefix'])) {
            $client->setOption(Redis::OPT_PREFIX, $server['prefix']);
        }

        if (!empty($server['read_timeout'])) {
            $client->setOption(Redis::OPT_READ_TIMEOUT, $server['read_timeout']);
        }

        if (!empty($server['password'])) {
            $client->auth($server['password']);
        }

        if (!empty($server['database'])) {
            $client->select($server['database']);
        }

        return $client;
    }
}
