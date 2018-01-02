<?php

namespace Illuminate\Cache;

use InvalidArgumentException;
use Illuminate\Support\Manager;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

/**
 * @mixin \Illuminate\Contracts\Cache\Repository
 */
class CacheManager extends Manager implements FactoryContract
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Create an instance of the APC cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\ApcStore
     */
    protected function createApcDriver(array $config)
    {
        $prefix = $this->getPrefix($config);

        return $this->repository(new ApcStore(new ApcWrapper, $prefix));
    }

    /**
     * Create an instance of the array cache driver.
     *
     * @return \Illuminate\Cache\ArrayStore
     */
    protected function createArrayDriver()
    {
        return $this->repository(new ArrayStore);
    }

    /**
     * Create an instance of the file cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\FileStore
     */
    protected function createFileDriver(array $config)
    {
        return $this->repository(new FileStore($this->app['files'], $config['path']));
    }

    /**
     * Create an instance of the Memcached cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\MemcachedStore
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
     * @return \Illuminate\Cache\NullStore
     */
    protected function createNullDriver()
    {
        return $this->repository(new NullStore);
    }

    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\RedisStore
     */
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];

        $connection = $config['connection'] ?? 'default';

        return $this->repository(new RedisStore($redis, $this->getPrefix($config), $connection));
    }

    /**
     * Create an instance of the database cache driver.
     *
     * @param  array  $config
     * @return \Illuminate\Cache\DatabaseStore
     */
    protected function createDatabaseDriver(array $config)
    {
        $connection = $this->app['db']->connection($config['connection'] ?? null);

        return $this->repository(
            new DatabaseStore(
                $connection, $config['table'], $this->getPrefix($config)
            )
        );
    }

    /**
     * Create a new cache repository with the given implementation.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return \Illuminate\Cache\Repository
     */
    public function repository(Store $store)
    {
        $repository = new Repository($store);

        if ($this->app->bound(DispatcherContract::class)) {
            $repository->setEventDispatcher(
                $this->app[DispatcherContract::class]
            );
        }

        return $repository;
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
     * @return array
     */
    protected function getConfig($name)
    {
        $config = $this->app['config']["cache.stores.{$name}"];

        if (is_null($config)) {
            throw new InvalidArgumentException("Cache store [{$name}] is not defined.");
        }

        return $config;
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
}
