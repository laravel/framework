<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Log\Writer;
use Monolog\Logger as Monolog;
use Illuminate\Contracts\Foundation\Application;

class ConfigureLogging {

	/**
	 * Log file name
	 *
	 * @var string
	 */
	protected $logfile;

	/**
	 * Application
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Log Writer
	 *
	 * @var Writer
	 */
	protected $log;

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$this->configureHandlers($app, $this->registerLogger($app));

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
	 * @param  \Illuminate\Contracts\Foundation\Application $app
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
	 * @param  \Illuminate\Contracts\Foundation\Application $app
	 * @param  \Illuminate\Log\Writer $log
	 * @return void
	 */
	protected function configureHandlers(Application $app, Writer $log)
	{
		$this->app = $app;
		$this->log = $log;
		$this->logfile = $app->storagePath() . '/logs/laravel.log';

		if (!empty($app['config']['app.logfile'])) {
			$this->logfile = $app['config']['app.logfile'];
		}

		$method = "configure" . ucfirst($app['config']['app.log']) . "Handler";

		$this->{$method}();
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @return void
	 */
	protected function configureSingleHandler()
	{
		$this->log->useFiles($this->logfile);
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @return void
	 */
	protected function configureDailyHandler()
	{
		$this->log->useDailyFiles($this->logfile, 5);
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @return void
	 */
	protected function configureSyslogHandler()
	{
		$this->log->useSyslog('laravel');
	}

}
