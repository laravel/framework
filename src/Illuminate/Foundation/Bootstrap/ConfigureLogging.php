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
		$app->instance('log', new Writer(
			new Monolog($app->environment()), $app['events'])
		);

		// Next, we will bind a Closure that resolves the PSR logger implementation
		// as this will grant us the ability to be interoperable with many other
		// libraries which are able to utilize the PSR standardized interface.
		$app->bind('Psr\Log\LoggerInterface', function($app)
		{
			return $app['log']->getMonolog();
		});
	}

}
