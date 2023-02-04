<?php

namespace Illuminate\Contracts\Foundation;

interface IsBootstrapper
{
    /**
     * Bootstrap the given application.
     *
     * @param  Application  $app
     * @return void
     */
    public function bootstrap(Application $app);
}
