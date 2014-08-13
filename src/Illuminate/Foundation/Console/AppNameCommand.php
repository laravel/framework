<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class AppNameCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'app:name';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Set the application namespace";

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new key generator command.
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
		if ( ! $this->coreIsReady())
		{
			return $this->error('Core directory has already been modified.');
		}

		$this->setUserClassNamespace();

		$this->setComposerNamespace();

		$this->setAuthConfigNamespace();

		$this->info('Application namespace set!');

		$this->call('dump-autoload');
	}

	/**
	 * Set the namespace in the Core User class.
	 *
	 * @return void
	 */
	protected function setUserClassNamespace()
	{
		$contents = $this->files->get($this->getUserClassPath());

		$contents = str_replace(
			'namespace App', 'namespace '.$this->argument('name'), $contents
		);

		$this->files->put($this->getUserClassPath(), $contents);
	}

	/**
	 * Set the PSR-4 namespace in the Composer file.
	 *
	 * @return void
	 */
	protected function setComposerNamespace()
	{
		$contents = $this->files->get($path = $this->getComposerPath());

		$this->files->put(
			$path, str_replace('App\\\\', $this->argument('name').'\\\\', $contents)
		);
	}

	/**
	 * Set the authentication User namespace.
	 *
	 * @return void
	 */
	protected function setAuthConfigNamespace()
	{
		$contents = $this->files->get($path = $this->getAuthConfigPath());

		$this->files->put($path, str_replace('App\\User', $this->argument('name').'\\User', $contents));
	}

	/**
	 * Determine if the Core directory User has been modified.
	 *
	 * @return bool
	 */
	protected function coreIsReady()
	{
		if ($this->files->exists($path = $this->getUserClassPath()))
		{
			return str_contains($this->files->get($path), 'namespace App;');
		}

		return false;
	}

	/**
	 * Get the path to the Core User class.
	 *
	 * @return string
	 */
	protected function getUserClassPath()
	{
		return $this->laravel['path'].'/Core/User.php';
	}

	/**
	 * Get the path to the Composer.json file.
	 *
	 * @return string
	 */
	protected function getComposerPath()
	{
		return $this->laravel['path.base'].'/composer.json';
	}

	/**
	 * Get the path to the authentication configuration file.
	 *
	 * @return string
	 */
	protected function getAuthConfigPath()
	{
		return $this->laravel['path.config'].'/auth.php';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('name', InputArgument::REQUIRED, 'The desired namespace.'),
		);
	}

}
