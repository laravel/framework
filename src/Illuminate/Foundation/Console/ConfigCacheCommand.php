<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ConfigCacheCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'config:cache';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a cache file for faster configuration loading';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->call('config:clear');

		$config = $this->getFreshConfiguration();

		$config = $this->setRealSessionDriver($config);

		file_put_contents(
			$this->laravel->getCachedConfigPath(), '<?php return '.var_export($config, true).';'.PHP_EOL
		);

		$this->info('Configuration cached successfully!');
	}

	/**
	 * Boot a fresh copy of the application configuration.
	 *
	 * @return \Illuminate\Routing\RouteCollection
	 */
	protected function getFreshConfiguration()
	{
		$app = require $this->laravel['path.base'].'/bootstrap/app.php';

		$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

		return $app['config']->all();
	}

	/**
	 * Set the "real" session driver on the configuratoin array.
	 *
	 * Typically the SessionManager forces the driver to "array" in CLI environment.
	 *
	 * @param  array  $config
	 * @return array
	 */
	protected function setRealSessionDriver(array $config)
	{
		$session = require $this->laravel->configPath().'/session.php';

		$config['session']['driver'] = $session['driver'];

		return $config;
	}

}
