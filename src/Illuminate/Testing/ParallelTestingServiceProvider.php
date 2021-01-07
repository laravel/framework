<?php

namespace Illuminate\Testing;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\Concerns\TemporaryDatabases;

class ParallelTestingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    use TemporaryDatabases;

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningUnitTests()) {
            $this->bootTemporaryDatabases();
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningUnitTests()) {
            $this->app->singleton(ParallelTesting::class, function () {
                return new ParallelTesting();
            });
        }
    }
}
