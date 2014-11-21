<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Config\FileLoader;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

class LoadConfiguration {

	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$app->instance('config', $config = new Repository(
			new FileLoader(new Filesystem, $app['path.config']), $app->environment()
		));

		date_default_timezone_set($config['app.timezone']);
	}

}
