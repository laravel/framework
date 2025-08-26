<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}
