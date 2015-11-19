<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use InvalidArgumentException;
use Illuminate\Contracts\Foundation\Application;

class DetectEnvironment
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        try {
            (new Dotenv($app->environmentPath(), $app->environmentFile()))->load();
        } catch (InvalidArgumentException $e) {
            //
        }

        $app->detectEnvironment(function () {
            return env('APP_ENV', 'production');
        });
    }
}
