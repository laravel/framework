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
        $url = $app->make('config')->get('app.url', 'http://localhost');
        $urlParts = parse_url($url);
        if (isset($urlParts['path']) && mb_strlen($urlParts['path']) > 1) {
            $_SERVER['SCRIPT_NAME'] = $urlParts['path'].DIRECTORY_SEPARATOR.'index.php';
            $_SERVER['SCRIPT_FILENAME'] = getenv('PWD').DIRECTORY_SEPARATOR.$urlParts['path'].DIRECTORY_SEPARATOR.'index.php';
        }
        $app->instance('request', Request::create($url, 'GET', [], [], [], $_SERVER));
    }
}
