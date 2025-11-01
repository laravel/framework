<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Foundation\Bootstrap\LoadConfiguration;

trait WithCachedConfig
{
    protected static array $cachedConfig = [];

    protected function setUpWithCachedConfig(): void
    {
        if ((self::$cachedConfig ?? null) === null) {
            self::$cachedConfig = $this->app->make('config')->all();
        }

        $this->app->instance('config.cached', true);
        LoadConfiguration::setAlwaysUseConfig(static fn () => self::$cachedConfig);
    }

    protected function tearDownWithCachedConfig(): void
    {
        LoadConfiguration::setAlwaysUseConfig(null);
    }
}
