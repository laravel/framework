<?php

namespace Illuminate\Cache;

use Aws\DynamoDb\DynamoDbClient;
use Closure;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * @mixin \Illuminate\Contracts\Cache\Repository
 */
class CacheManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved cache stores.
     *
     * @var array
     */
    protected $stores = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new Cache manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a cache store instance by name, wrapped in a repository.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] = $this->get($name);
    }

    /**
     * Get a cache driver instance.
     *
     * @param  string|null  $driver
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function driver($driver = null)
    {
        return $this->store($driver);
    }

    /**
     * Attempt to get the store from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function get($name)
    {
        return $this->stores[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $name
     * @return \Illuminate\Contracts\Cache\Repository
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        } else {
            $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

            if (method_exists($this, $driverMethod)) {
                return $this->{$driverMethod}($config);
            } else {
                throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
            }
        }
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the APC cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createApcDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        return $this->repository(new ApcStore(new ApcWrapper, $prefix));
    }

    /**
     * Create an instance of the array cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createArrayDriver(array $config)
    {
        return $this->repository(new ArrayStore($config['serialize'] ?? false));
    }

    /**
     * Create an instance of the file cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createFileDriver(array $config)
    {
        return $this->repository(new FileStore($this->app['files'], $config['path'], $config['permission'] ?? null));
    }

    /**
     * Create an instance of the Memcached cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createMemcachedDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        $memcached = $this->app['memcached.connector']->connect(
            $config['servers'],
            $config['persistent_id'] ?? null,
            $config['options'] ?? [],
            array_filter($config['sasl'] ?? [])
        );

        return $this->repository(new MemcachedStore($memcached, $prefix));
    }

    /**
     * Create an instance of the Null cache driver.
     *
     * @return \Illuminate\Cache\Repository
     */
    protected function createNullDriver()
    {
        return $this->repository(new NullStore);
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];

        $connection = $config['connection'] ?? 'default';

        $store = new RedisStore($redis, $this->getPrefix($config), $connection);

        return $this->repository(
            $store->setLockConnection($config['lock_connection'] ?? $connection)
        );
    }

    /**
     * Create an instance of the database cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createDatabaseDriver(array $config)
    {
        $connection = $this->app['db']->connection($config['connection'] ?? null);

        $store = new DatabaseStore(
            $connection,
            $config['table'],
            $this->getPrefix($config),
            $config['lock_table'] ?? 'cache_locks',
            $config['lock_lottery'] ?? [2, 100]
        );

        return $this->repository($store->setLockConnection(
            $this->app['db']->connection($config['lock_connection'] ?? $config['connection'] ?? null)
        ));
    }

    /**
     * Create an instance of the DynamoDB cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\Repository
     */
    protected function createDynamodbDriver(array $config)
    {
        $client = $this->newDynamodbClient($config);

        return $this->repository(
            new DynamoDbStore(
                $client,
                $config['table'],
                $config['attributes']['key'] ?? 'key',
                $config['attributes']['value'] ?? 'value',
                $config['attributes']['expiration'] ?? 'expires_at',
                $this->getPrefix($config)
            )
        );
    }

    /**
     * Create new DynamoDb Client instance.
     *
     * @return \Aws\DynamoDb\DynamoDbClient
     */
    protected function newDynamodbClient(array $config)
    {
        $dynamoConfig = [
            'region' => $config['region'],
            'version' => 'latest',
            'endpoint' => $config['endpoint'] ?? null,
        ];

        if (isset($config['key']) && isset($config['secret'])) {
            $dynamoConfig['credentials'] = Arr::only(
                $config, ['key', 'secret', 'token']
            );
        }

        return new DynamoDbClient($dynamoConfig);
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return \Illuminate\Cache\Repository
     */
    public function repository(Store $store)
    {
        return tap(new Repository($store), function ($repository) {
            $this->setEventDispatcher($repository);
        });
    }

    /**
     * Set the event dispatcher on the given repository instance.
     *
     * @param  \Illuminate\Cache\Repository  $repository
     * @return void
     */
    protected function setEventDispatcher(Repository $repository)
    {
        if (! $this->app->bound(DispatcherContract::class)) {
            return;
        }

        $repository->setEventDispatcher(
            $this->app[DispatcherContract::class]
        );
    }

    /**
     * Re-set the event dispatcher on all resolved cache repositories.
     *
     * @return void
     */
    public function refreshEventDispatcher()
    {
        array_map([$this, 'setEventDispatcher'], $this->stores);
    }

    /**
     * Get the cache prefix.
     *
     * @param  array  $config
     * @return string
     */
    protected function getPrefix(array $config)
    {
        return $config['prefix'] ?? $this->app['config']['cache.prefix'];
    }

    /**
     * Get the cache connection configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->app['config']["cache.stores.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the default cache driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['cache.default'];
    }

    /**
     * Set the default cache driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['cache.default'] = $name;
    }

    /**
     * Unset the given driver instances.
     *
     * @param  array|string|null  $name
     * @return $this
     */
    public function forgetDriver($name = null)
    {
        $name = $name ?? $this->getDefaultDriver();

        foreach ((array) $name as $cacheName) {
            if (isset($this->stores[$cacheName])) {
                unset($this->stores[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * Disconnect the given driver and remove from local cache.
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge($name = null)
    {
        $name = $name ?? $this->getDefaultDriver();

        unset($this->stores[$name]);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @param  \Closure  $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}
