<?php

namespace Illuminate\Foundation;

use Illuminate\Support\Manager;

class MaintenanceModeManager extends Manager
{
    /**
     * Create an instance of the file based maintenance driver.
     */
    protected function createFileDriver(): FileBasedMaintenanceMode
    {
        return new FileBasedMaintenanceMode();
    }

    /**
     * Create an instance of the cache based maintenance driver.
     *
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createCacheDriver(): CacheBasedMaintenanceMode
    {
        return new CacheBasedMaintenanceMode(
            $this->container->make('cache'),
            $this->config->get('app.maintenance.store') ?: $this->config->get('cache.default'),
            'illuminate:foundation:down'
        );
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('app.maintenance.driver', 'file');
    }
}
