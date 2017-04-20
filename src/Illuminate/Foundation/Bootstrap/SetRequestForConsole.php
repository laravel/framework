<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Http\Request;
use Illuminate\Contracts\Foundation\Application;

class SetRequestForConsole
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new BootProviders instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap()
    {
        $url = $this->app->make('config')->get('app.url', 'http://localhost');

        $this->app->instance('request', Request::create($url, 'GET', [], [], [], $_SERVER));
    }
}
