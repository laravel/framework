<?php namespace Illuminate\Foundation\Bootstrap;

use Dotenv;
use InvalidArgumentException;
use Illuminate\Contracts\Foundation\Application;

class DetectEnvironment {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		try
		{
			Dotenv::load($app['path.base']);
		}
		catch (InvalidArgumentException $e)
		{
			//
		}

		$app->detectEnvironment(function()
		{
			return getenv('APP_ENV') ?: 'production';
		});
	}

}
