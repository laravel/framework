<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Uri\Rfc3986\Uri;

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
        $uri = $app->make('config')->get('app.url', 'http://localhost');

        $components = new Uri($uri);

        $server = $_SERVER;

        if ($path = $components->getPath()) {
            $server = array_merge($server, [
                'SCRIPT_FILENAME' => $path,
                'SCRIPT_NAME' => $path,
            ]);
        }

        $app->instance('request', Request::create(
            $uri, 'GET', [], [], [], $server
        ));
    }
}
