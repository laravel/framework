<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Log\Writer;
use Monolog\Logger as Monolog;
use Illuminate\Contracts\Foundation\Application;

class ConfigureLogging {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$this->configureHandlers($app, $this->registerLogger($app));

		// Next, we will bind a Closure that resolves the PSR logger implementation
		// as this will grant us the ability to be interoperable with many other
		// libraries which are able to utilize the PSR standardized interface.
		$app->bind('Psr\Log\LoggerInterface', function($app)
		{
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
		$method = "configure".ucfirst($app['config']['app.log'])."Handler";

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
		$log->useFiles($app->storagePath().'/logs/laravel.log');
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
		$log->useDailyFiles(
			$app->storagePath().'/logs/laravel.log',
			$app->make('config')->get('app.log_max_files', 5)
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
		$log->useSyslog('laravel');
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
		$log->useErrorLog();
	}

}
