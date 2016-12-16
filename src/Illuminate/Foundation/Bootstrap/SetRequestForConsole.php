<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;

class SetRequestForConsole
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->instance('request', Request::create(
            $app->make('config')->get('app.url', 'http://localhost'), 'GET', [], [], [], $_SERVER
        ));
    }
}
