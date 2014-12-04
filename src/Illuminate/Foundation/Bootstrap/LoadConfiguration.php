<?php namespace Illuminate\Foundation\Bootstrap;

use Illuminate\Config\FileLoader;
use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;
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
		$app->instance('config', $config = new Repository);

		foreach ($this->getConfigurationFiles() as $key => $path)
		{
			$config->set($key, require $path);
		}

		date_default_timezone_set($config['app.timezone']);
	}

	/**
	 * Get all of the configuration files for the application.
	 *
	 * @return array
	 */
	protected function getConfigurationFiles()
	{
		$files = [];

		foreach (Finder::create()->files()->in(base_path('config')) as $file)
		{
			$files[basename($file->getRealPath(), '.php')] = $file->getRealPath();
		}

		return $files;
	}

}
