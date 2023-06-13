<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\Providers;

use Illuminate\Console\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Tests\Integration\Foundation\Fixtures\Console\ThrowExceptionCommand;

class ThrowExceptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Application::starting(function ($artisan) {
            $artisan->add(new ThrowExceptionCommand);
        });
    }
}
