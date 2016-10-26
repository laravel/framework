<?php

namespace Illuminate\Log;

use Monolog\Logger as Monolog;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('log', function () {
            return $this->createLogger();
        });
    }

    /**
     * Create the logger.
     *
     * @return \Illuminate\Log\Writer
     */
    public function createLogger()
    {
        $log = new Writer(
            new Monolog($this->app->bound('env') ? $this->app->environment() : 'production'), $this->app['events']
        );

        if ($this->app->hasMonologConfigurator()) {
            call_user_func(
                $this->app->getMonologConfigurator(), $log->getMonolog()
            );
        } else {
            $this->configureHandlers($log);
        }

        return $log;
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureHandlers(Writer $log)
    {
        if ($this->app->bound('config')) {
            $handler = $this->app->make('config')->get('app.log');
        } else {
            $handler = 'single';
        }

        $method = 'configure'.ucfirst($handler).'Handler';

        $this->{$method}($log);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSingleHandler(Writer $log)
    {
        $log->useFiles(
            $this->app->storagePath().'/logs/laravel.log',
            $this->logLevel()
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Writer $log)
    {
        $log->useDailyFiles(
            $this->app->storagePath().'/logs/laravel.log', $this->maxFiles(),
            $this->logLevel()
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSyslogHandler(Writer $log)
    {
        $log->useSyslog('laravel', $this->logLevel());
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureErrorlogHandler(Writer $log)
    {
        $log->useErrorLog($this->logLevel());
    }

    /**
     * Get the log level for the application.
     *
     * @return string
     */
    protected function logLevel()
    {
        if ($this->app->bound('config')) {
            return $this->app->make('config')->get('app.log_level');
        }

        return 'debug';
    }

    /**
     * Get the maximum number of log files for the application.
     *
     * @return int
     */
    protected function maxFiles()
    {
        if ($this->app->bound('config')) {
            return $this->app->make('config')->get('app.log_max_files', 5);
        }

        return 0;
    }
}
