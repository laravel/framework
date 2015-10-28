<?php

namespace Illuminate\Foundation\Bootstrap;
use Illuminate\Foundation as help;

use Dotenv;
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
            Dotenv::load($app->environmentPath(), $app->environmentFile());
        } catch (InvalidArgumentException $e) {
            //
        }

        $app->detectEnvironment(function () {
            return help\env('APP_ENV', 'production');
        });
    }
}
