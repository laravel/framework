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
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new config cache command instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->call('config:clear');

		$config = $this->setRealSessionDriver(
			$this->getFreshConfiguration()
		);

		$this->files->put(
			$this->laravel->getCachedConfigPath(), '<?php return '.var_export($config, true).';'.PHP_EOL
		);

		$this->info('Configuration cached successfully!');
	}

	/**
	 * Boot a fresh copy of the application configuration.
	 *
	 * @return array
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
