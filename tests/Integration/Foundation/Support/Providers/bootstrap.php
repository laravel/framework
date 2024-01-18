<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use function Orchestra\Testbench\default_skeleton_path;

/**
 * The first thing we will do is create a new Laravel application instance
 * which serves as the brain for all of the Laravel components. We will
 * also use the application to configure core, foundational behavior.
 */

return Application::configure(default_skeleton_path())
    ->withProviders()
    ->withRouting(
        using: function () {
            Route::get('login', fn () => 'Login')->name('login');
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
