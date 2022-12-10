<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Contracts\Foundation\Vite as ViteContract;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteManager;
use Illuminate\Support\ServiceProvider;

class ViteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('vite', fn ($app) => new ViteManager($app));
        $this->app->singleton('vite.app', fn ($app) => $app['vite']->app());
        $this->app->bind(ViteContract::class, 'vite.app');
        $this->app->bind(Vite::class, 'vite.app');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['vite', 'vite.app', ViteContract::class, Vite::class];
    }
}
