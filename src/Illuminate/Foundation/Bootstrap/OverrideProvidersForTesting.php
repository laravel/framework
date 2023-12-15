<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseConnectionFactory;

class OverrideProvidersForTesting
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if (! $app->runningUnitTests()) {
            return;
        }

        if ($app->bound('db.factory')) {
            tap($app['db.factory'], function ($factory) use ($app) {
                $app->instance('db.factory', new DatabaseConnectionFactory($app, $factory));
            });
        }
    }
}
