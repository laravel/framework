<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Foundation\Application;

class RegisterFacades
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
        Facade::clearResolvedInstances();

        Facade::setFacadeApplication($this->app);

        AliasLoader::getInstance($this->app->make('config')->get('app.aliases', []))->register();
    }
}
