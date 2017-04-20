<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;

class DetectEnvironment
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
        if (! $this->app->configurationIsCached()) {
            $this->checkForSpecificEnvironmentFile();

            try {
                (new Dotenv($this->app->environmentPath(), $this->app->environmentFile()))->load();
            } catch (InvalidPathException $e) {
                //
            }
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile()
    {
        if (php_sapi_name() == 'cli') {
            $input = new ArgvInput;

            if ($input->hasParameterOption('--env')) {
                $file = $this->app->environmentFile().'.'.$input->getParameterOption('--env');

                $this->loadEnvironmentFile($file);
            }
        }

        if (! env('APP_ENV')) {
            return;
        }

        if (empty($file)) {
            $file = $this->app->environmentFile().'.'.env('APP_ENV');

            $this->loadEnvironmentFile($file);
        }
    }

    /**
     * Load a custom environment file.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $file
     * @return void
     */
    protected function loadEnvironmentFile($file)
    {
        if (file_exists($this->app->environmentPath().'/'.$file)) {
            $this->app->loadEnvironmentFrom($file);
        }
    }
}
