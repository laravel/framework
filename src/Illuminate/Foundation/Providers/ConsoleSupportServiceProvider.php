<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Database\SeedServiceProvider;
use Illuminate\Queue\ConsoleServiceProvider;
use Illuminate\Auth\GeneratorServiceProvider;
use Illuminate\Console\ScheduleServiceProvider;
use Illuminate\Session\CommandsServiceProvider;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Database\MigrationServiceProvider;

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
        GeneratorServiceProvider::class,
        ScheduleServiceProvider::class,
        MigrationServiceProvider::class,
        SeedServiceProvider::class,
        ComposerServiceProvider::class,
        ConsoleServiceProvider::class,
        GeneratorServiceProvider::class,
        CommandsServiceProvider::class,
    ];
}
