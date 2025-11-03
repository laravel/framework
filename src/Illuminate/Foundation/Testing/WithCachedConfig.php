<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

trait WithCachedConfig
{
    protected function setUpWithCachedConfig(): void
    {
        if ((CachedState::$cachedConfig ?? null) === null) {
            CachedState::$cachedConfig = $this->app->make('config')->all();
        }

        $this->markConfigAsCached($this->app);
    }

    protected function tearDownWithCachedConfig(): void
    {
        LoadConfiguration::setAlwaysUseConfig(null);
    }

    /**
     * Inform the container to treat configuration as cached.
     */
    protected function markConfigAsCached(Application $app): void
    {
        $app->instance('config_loaded_from_cache', true); // I'm not sure this is actually needed

        LoadConfiguration::setAlwaysUseConfig(static fn () => CachedState::$cachedConfig);
    }
}
