<?php

namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Log\Writer;
use Monolog\Logger as Monolog;
use Illuminate\Contracts\Foundation\Application;

class ConfigureLogging
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $log = $this->registerLogger($app);

        // If a custom Monolog configurator has been registered for the application
        // we will call that, passing Monolog along. Otherwise, we will grab the
        // the configurations for the log system and use it for configuration.
        if ($app->hasMonologConfigurator()) {
            call_user_func(
                $app->getMonologConfigurator(), $log->getMonolog()
            );
        } else {
            $this->configureHandlers($app, $log);
        }

        // Next, we will bind a Closure that resolves the PSR logger implementation
        // as this will grant us the ability to be interoperable with many other
        // libraries which are able to utilize the PSR standardized interface.
        $app->bind('Psr\Log\LoggerInterface', function ($app) {
            return $app['log']->getMonolog();
        });
    }

    /**
     * Register the logger instance in the container.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Illuminate\Log\Writer
     */
    protected function registerLogger(Application $app)
    {
        $app->instance('log', $log = new Writer(
            new Monolog($app->environment()), $app['events'])
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
    protected function configureHandlers(Application $app, Writer $log)
    {
        $method = 'configure'.ucfirst($app['config']->get('app.log')).'Handler';
        $level = $app['config']->get('app.level', 'debug');
        $this->{$method}($app, $log, $level);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @param  string  $level minimum level to be logged
     * @return void
     */
    protected function configureSingleHandler(Application $app, Writer $log, $level = 'debug')
    {
        $log->useFiles($app->storagePath().'/logs/laravel.log', $level);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @param  string  $level minimum level to be logged
     * @return void
     */
    protected function configureDailyHandler(Application $app, Writer $log, $level = 'debug')
    {
        $log->useDailyFiles(
            $app->storagePath().'/logs/laravel.log',
            $app->make('config')->get('app.log_max_files', 5),
            $level
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @param  string  $level minimum level to be logged
     * @return void
     */
    protected function configureSyslogHandler(Application $app, Writer $log, $level = 'debug')
    {
        $log->useSyslog('laravel', $level);
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @param  string  $level minimum level to be logged
     * @return void
     */
    protected function configureErrorlogHandler(Application $app, Writer $log, $level = 'debug')
    {
        $log->useErrorLog($level);
    }
}
