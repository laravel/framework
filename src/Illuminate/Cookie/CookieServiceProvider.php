<?php

namespace Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\RegistrableProvider;

class CookieServiceProvider extends ServiceProvider implements RegistrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cookie', function ($app) {
            $config = $app->make('config')->get('session');

            return (new CookieJar)->setDefaultPathAndDomain(
                $config['path'], $config['domain'], $config['secure'], $config['same_site'] ?? null
            );
        });
    }
}
