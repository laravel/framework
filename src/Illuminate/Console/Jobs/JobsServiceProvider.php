<?php

namespace Illuminate\Console\Jobs;

use Illuminate\Support\ServiceProvider;

class JobsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands(DispatchCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            DispatchCommand::class,
        ];
    }
}
