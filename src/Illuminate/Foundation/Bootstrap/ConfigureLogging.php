<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Log\Writer;
use Monolog\Logger as Monolog;
use Illuminate\Contracts\Foundation\Application;

class ConfigureLogging
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
        $log = $this->registerLogger($this->$app);

        // If a custom Monolog configurator has been registered for the application
        // we will call that, passing Monolog along. Otherwise, we will grab the
        // the configurations for the log system and use it for configuration.
        if ($this->app->hasMonologConfigurator()) {
            call_user_func(
                $this->app->getMonologConfigurator(), $log->getMonolog()
            );
        } else {
            $this->configureHandlers($log);
        }
    }

    /**
     * Register the logger instance in the container.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Illuminate\Log\Writer
     */
    protected function registerLogger()
    {
        $this->app->instance('log', $log = new Writer(
            new Monolog($this->app->environment()), $app['events'])
        );

        return $log;
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureHandlers(Writer $log)
    {
        $method = 'configure'.ucfirst($app['config']['app.log']).'Handler';

        $this->{$method}($app, $log);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSingleHandler(Application $app, Writer $log)
    {
        $log->useFiles(
            $this->app->storagePath().'/logs/laravel.log',
            $this->app->make('config')->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Application $app, Writer $log)
    {
        $config = $this->app->make('config');

        $maxFiles = $config->get('app.log_max_files');

        $log->useDailyFiles(
            $this->app->storagePath().'/logs/laravel.log', is_null($maxFiles) ? 5 : $maxFiles,
            $config->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSyslogHandler(Application $app, Writer $log)
    {
        $log->useSyslog(
            'laravel',
            $this->app->make('config')->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureErrorlogHandler(Application $app, Writer $log)
    {
        $log->useErrorLog($this->app->make('config')->get('app.log_level', 'debug'));
    }
}
