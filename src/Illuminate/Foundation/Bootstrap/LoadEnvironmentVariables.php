<?php

namespace Illuminate\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Dotenv\Exception\InvalidPathException;
use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;

class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        $this->checkForSpecificEnvironmentFile($app);

        if ($app->environmentFile() !== '.env') {
            $this->load($app, '.local'); // .env.[APP_ENV].local
            $this->load($app); // .env.[APP_ENV]
        }

        $this->setEnvironmentFilePath($app, '.env');

        $this->load($app, '.local'); // .env.local file
        $this->load($app); // .env
    }

    /**
     * Load the current .env file, optionally with suffix.
     */
    protected function load(Application $app, $suffix = '')
    {
        try {
            (new Dotenv($app->environmentPath(), $app->environmentFile() . $suffix))->load();
        } catch (InvalidPathException $e) {
            //
        } catch (InvalidFileException $e) {
            die('The environment file is invalid: '.$e->getMessage());
        }
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile($app)
    {
        if ($app->runningInConsole() && ($input = new ArgvInput)->hasParameterOption('--env')) {
            if ($this->setEnvironmentFilePath(
                $app, $app->environmentFile().'.'.$input->getParameterOption('--env')
            )) {
                return;
            }
        }

        if (! env('APP_ENV')) {
            return;
        }

        $this->setEnvironmentFilePath(
            $app, $app->environmentFile().'.'.env('APP_ENV')
        );
    }

    /**
     * Load a custom environment file.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $file
     * @return bool
     */
    protected function setEnvironmentFilePath($app, $file)
    {
        if (file_exists($app->environmentPath().'/'.$file)) {
            $app->loadEnvironmentFrom($file);

            return true;
        }

        return false;
    }
}
