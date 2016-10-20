<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * The observer mappings for the application.
     *
     * @var array
     */
    protected $observers = [];

    /**
     * Register the application's observers.
     *
     * @return void
     */
    public function registerObservers()
    {
        foreach ($this->observers as $key => $value) {
            $key::observe($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }
}
