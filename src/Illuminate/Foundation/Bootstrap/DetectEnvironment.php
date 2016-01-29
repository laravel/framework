<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
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
        if (! $app->configurationIsCached()) {
            $this->detectCustomEnvFile($app);

            try {
                (new Dotenv($app->environmentPath(), $app->environmentFile()))->load();
            } catch (InvalidPathException $e) {
                //
            }
        }
    }

    /**
     * Detect if a custom env file matching the APP_ENV exists.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function detectCustomEnvFile($app)
    {
        $fileName = $app->environmentFile().'.'.env('APP_ENV');

        if (file_exists($app->environmentPath().'/'.$fileName)) {
            $app->loadEnvironmentFrom($fileName);
        }
    }
}
