<?php

namespace Laravel\Pail;

use Illuminate\Support\ServiceProvider;

// Stands in for the optional laravel/pail package so its provider can be registered.
if (! class_exists(PailServiceProvider::class)) {
    class PailServiceProvider extends ServiceProvider
    {
        //
    }
}
