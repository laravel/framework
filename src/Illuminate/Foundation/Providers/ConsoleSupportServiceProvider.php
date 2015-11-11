<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Support\AggregateServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The provider class names.
     *
     * @var array
     */
    protected $providers = [
        \Illuminate\Console\ScheduleServiceProvider::class,
        \Illuminate\Database\MigrationServiceProvider::class,
        \Illuminate\Database\SeedServiceProvider::class,
        \Illuminate\Foundation\Providers\ComposerServiceProvider::class,
        \Illuminate\Queue\ConsoleServiceProvider::class,
    ];
}
