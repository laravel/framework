<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    use WithEvents;

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [];

    /**
     * The subscribers to register.
     *
     * @var array
     */
    protected $subscribe = [];

    /**
     * The model observers to register.
     *
     * @var array
     */
    protected $observers = [];

    /**
     * Boot any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
