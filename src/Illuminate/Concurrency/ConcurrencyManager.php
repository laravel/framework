<?php

namespace Illuminate\Concurrency;

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\MultipleInstanceManager;

/**
 * @mixin \Illuminate\Contracts\Concurrency\Driver
 */
class ConcurrencyManager extends MultipleInstanceManager
{
    /**
     * Get a driver instance by name.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function driver($name = null)
    {
        return $this->instance($name);
    }

    /**
     * Create an instance of the process concurrency driver.
     *
     * @param  array  $config
     * @return \Illuminate\Concurrency\ProcessDriver
     */
    public function createProcessDriver(array $config)
    {
        return new ProcessDriver($this->app->make(ProcessFactory::class));
    }

    /**
     * Create an instance of the sync concurrency driver.
     *
     * @param  array  $config
     * @return \Illuminate\Concurrency\SyncDriver
     */
    public function createSyncDriver(array $config)
    {
        return new SyncDriver;
    }

    /**
     * Get the default instance name.
     *
     * @return string
     */
    public function getDefaultInstance()
    {
        return $this->app['config']['concurrency.default'];
    }

    /**
     * Set the default instance name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultInstance($name)
    {
        $this->app['config']['concurrency.default'] = $name;
    }

    /**
     * Get the instance specific configuration.
     *
     * @param  string  $name
     * @return array
     */
    public function getInstanceConfig($name)
    {
        return $this->app['config']->get(
            'concurrency.drivers.'.$name, ['driver' => $name],
        );
    }
}
