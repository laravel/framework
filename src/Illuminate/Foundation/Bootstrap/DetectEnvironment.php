<?php namespace Illuminate\Foundation\Bootstrap;

use Dotenv;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\Bootstrapper as BootstrapperContract;

class DetectEnvironment implements BootstrapperContract {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		if (file_exists($app['path.base'].'/.env'))
		{
			Dotenv::load($app['path.base']);
		}

		$app->detectEnvironment(function()
		{
			return getenv('APP_ENV') ?: 'production';
		});
	}

}
