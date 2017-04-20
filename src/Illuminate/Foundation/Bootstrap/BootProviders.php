<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class BootProviders
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
        $this->app->boot();
    }
}
