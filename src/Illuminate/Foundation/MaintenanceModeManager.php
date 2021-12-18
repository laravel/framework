<?php

namespace Illuminate\Foundation;

use Illuminate\Support\Manager;

class MaintenanceModeManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('app.maintenance.driver', 'file');
    }

    /**
     * Create an instance of the cache maintenance driver.
     *
     * @return \Illuminate\Foundation\CacheBasedMaintenanceMode
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createCacheDriver(): CacheBasedMaintenanceMode
    {
        $cache = $this->container->make('cache');
        $store = $this->config->get('app.maintenance.store') ?: $this->config->get('cache.default');
        $key = 'illuminate:foundation:down';

        return new CacheBasedMaintenanceMode($cache, $store, $key);
    }

    /**
     * Create an instance of the file maintenance driver.
     *
     * @return \Illuminate\Foundation\FileBasedMaintenanceMode
     */
    protected function createFileDriver(): FileBasedMaintenanceMode
    {
        return new FileBasedMaintenanceMode();
    }
}
