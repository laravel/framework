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
		$app->instance('log', new Writer(new Monolog($app->environment()), $app['events']));

		$app->bind('Psr\Log\LoggerInterface', function()
		{
			return $app['log']->getMonolog();
		});
	}

}
