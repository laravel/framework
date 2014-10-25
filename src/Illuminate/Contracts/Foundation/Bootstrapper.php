<?php namespace Illuminate\Contracts\Foundation;

interface Bootstrapper {
    
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app);

}