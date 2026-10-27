<?php

namespace Illuminate\Foundation;

use Illuminate\Support\Manager;

class MaintenanceModeManager extends Manager
{
    /**
     * Create an instance of the file based maintenance driver.
     *
     * @return \Illuminate\Foundation\FileBasedMaintenanceMode
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createFileDriver(): FileBasedMaintenanceMode
    {
        return new FileBasedMaintenanceMode($this->container->make('events'));
    }

    /**
     * Create an instance of the array based maintenance driver.
     *
     * @return \Illuminate\Foundation\ArrayMaintenanceMode
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createArrayDriver(): ArrayMaintenanceMode
    {
        return new ArrayMaintenanceMode($this->container->make('events'));
    }

    /**
     * Create an instance of the cache based maintenance driver.
     *
     * @return \Illuminate\Foundation\CacheBasedMaintenanceMode
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createCacheDriver(): CacheBasedMaintenanceMode
    {
        return new CacheBasedMaintenanceMode(
            $this->container->make('cache'),
            $this->config->get('app.maintenance.store') ?: $this->config->get('cache.default'),
            'illuminate:foundation:down',
            $this->container->make('events')
        );
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('app.maintenance.driver', 'file');
    }
}
