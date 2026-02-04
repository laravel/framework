<?php

namespace Illuminate\Redis\Connections;

use Closure;
use Illuminate\Contracts\Redis\Connection as ConnectionContract;
use Illuminate\Redis\RedisManager;
use RuntimeException;
use Throwable;

class FailoverConnection extends Connection implements ConnectionContract
{
    /**
     * The Redis manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected RedisManager $manager;

    /**
     * The normalized connection configurations.
     *
     * @var array<int, array{name: string, read_only: bool}>
     */
    protected array $connectionConfigs = [];

    /**
     * Resolved connections (lazy).
     *
     * @var array<string, \Illuminate\Redis\Connections\Connection>
     */
    protected array $connections = [];

    /**
     * Redis commands that are read-only and may use read-only connections.
     *
     * @var array<string, true>
     */
    protected static array $readOnlyCommands = [
        'get' => true,
        'mget' => true,
        'exists' => true,
        'type' => true,
        'ttl' => true,
        'pttl' => true,
        'llen' => true,
        'lindex' => true,
        'lrange' => true,
        'zcard' => true,
        'zrange' => true,
        'zrangebyscore' => true,
        'zrank' => true,
        'zrevrank' => true,
        'zcount' => true,
        'zscore' => true,
        'hget' => true,
        'hgetall' => true,
        'hlen' => true,
        'hmget' => true,
        'hexists' => true,
        'hkeys' => true,
        'hvals' => true,
        'scan' => true,
        'sscan' => true,
        'hscan' => true,
        'zscan' => true,
        'keys' => true,
        'dump' => true,
        'getrange' => true,
        'strlen' => true,
        'smembers' => true,
        'sismember' => true,
        'scard' => true,
        'srandmember' => true,
        'sdiff' => true,
        'sinter' => true,
        'sunion' => true,
        'ping' => true,
        'time' => true,
        'info' => true,
        'dbsize' => true,
        'object' => true,
        'randomkey' => true,
        'bitcount' => true,
        'bitpos' => true,
        'getbit' => true,
        'pfcount' => true,
        'geohash' => true,
        'geopos' => true,
        'geodist' => true,
        'georadius_ro' => true,
        'georadiusbymember_ro' => true,
        'lpos' => true,
        'xlen' => true,
        'xrange' => true,
        'xrevrange' => true,
        'xread' => true,
        'xinfo' => true,
        'xpending' => true,
    ];

    /**
     * Create a new failover connection instance.
     *
     * @param  \Illuminate\Redis\RedisManager  $manager
     * @param  array  $connections  Array of connection names or ['name' => string, 'read_only' => bool] arrays
     */
    public function __construct(RedisManager $manager, array $connections)
    {
        $this->manager = $manager;
        $this->connectionConfigs = $this->normalizeConnections($connections);

        if (empty($this->connectionConfigs)) {
            throw new RuntimeException('At least one connection must be specified for failover.');
        }
    }

    /**
     * Normalize connections to array of ['name' => string, 'read_only' => bool].
     *
     * @param  array  $connections
     * @return array<int, array{name: string, read_only: bool}>
     */
    protected function normalizeConnections(array $connections): array
    {
        $normalized = [];

        foreach ($connections as $connection) {
            if (is_string($connection)) {
                $normalized[] = [
                    'name' => $connection,
                    'read_only' => $this->manager->getConnectionConfig($connection)['read_only'] ?? false,
                ];
            } elseif (is_array($connection) && isset($connection['name'])) {
                $name = $connection['name'];
                $normalized[] = [
                    'name' => $name,
                    'read_only' => $connection['read_only']
                        ?? $this->manager->getConnectionConfig($name)['read_only']
                        ?? false,
                ];
            }
        }

        return $normalized;
    }

    /**
     * Run a command against the Redis database.
     *
     * Read commands try all connections in order.
     * Write commands only try connections where read_only is false.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \Throwable
     */
    public function command($method, array $parameters = [])
    {
        $method = strtolower($method);
        $isReadCommand = isset(static::$readOnlyCommands[$method]);

        if ($isReadCommand) {
            return $this->attemptOnConnections($method, $parameters, $this->connectionConfigs);
        }

        $writableConnections = array_values(array_filter(
            $this->connectionConfigs,
            fn ($config) => ! $config['read_only']
        ));

        if (empty($writableConnections)) {
            throw new RuntimeException('No writable Redis connections available in failover.');
        }

        return $this->attemptOnConnections($method, $parameters, $writableConnections);
    }

    /**
     * Attempt the command on each connection in order.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @param  array<int, array{name: string, read_only: bool}>  $configs
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function attemptOnConnections(string $method, array $parameters, array $configs)
    {
        $lastException = null;

        foreach ($configs as $config) {
            try {
                return $this->resolveConnection($config['name'])->command($method, $parameters);
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        throw $lastException ?? new RuntimeException('All failover Redis connections failed.');
    }

    /**
     * Resolve a connection by name (lazy).
     *
     * @param  string  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function resolveConnection(string $name): Connection
    {
        if (! isset($this->connections[$name])) {
            $this->connections[$name] = $this->manager->connection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Get the writable connection names.
     *
     * @return array<int, string>
     */
    public function getWritableConnections(): array
    {
        return array_map(
            fn ($config) => $config['name'],
            array_filter($this->connectionConfigs, fn ($config) => ! $config['read_only'])
        );
    }

    /**
     * Get all connection names.
     *
     * @return array<int, string>
     */
    public function getConnections(): array
    {
        return array_map(fn ($config) => $config['name'], $this->connectionConfigs);
    }

    /**
     * Subscribe to a set of given channels for messages.
     *
     * @param  array|string  $channels
     * @param  \Closure  $callback
     * @param  string  $method
     * @return void
     */
    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        // Use first connection for subscriptions
        $this->resolveConnection($this->connectionConfigs[0]['name'])
            ->createSubscription($channels, $callback, $method);
    }

    /**
     * Get the underlying Redis client (first connection).
     *
     * @return mixed
     */
    public function client()
    {
        return $this->resolveConnection($this->connectionConfigs[0]['name'])->client();
    }
}
