<?php

namespace Illuminate\Console;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\ScheduleRunCommand;

class ScheduleServiceProvider extends ServiceProvider
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
        $this->commands(ScheduleRunCommand::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            ScheduleRunCommand::class,
        ];
    }
}
