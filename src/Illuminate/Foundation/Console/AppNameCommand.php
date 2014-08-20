<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
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
		$this->namespaceAppDirectory();

		$this->setComposerNamespace();

		$this->setConfigNamespaces();

		$this->info('Application namespace set!');

		$this->call('dump-autoload');
	}

	/**
	 * Set the namespace on the files in the app directory.
	 *
	 * @return void
	 */
	protected function namespaceAppDirectory()
	{
		$files = Finder::create()
                            ->in($this->laravel['path'])
                            ->exclude($this->laravel['path'].'/Http/Views')
                            ->name('*.php');

		foreach ($files as $file)
		{
			$this->replaceNamespace($file->getRealPath());
		}
	}

	/**
	 * Replace the App namespace at the given path.
	 *
	 * @param  string  $path;
	 */
	protected function replaceNamespace($path)
	{
		$contents = $this->files->get($path);

		$contents = str_replace(
			'namespace '.$this->root().'\\', 'namespace '.$this->argument('name').'\\', $contents
		);

		$this->files->put($path, $contents);
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
			$path, str_replace($this->root().'\\\\', $this->argument('name').'\\\\', $contents)
		);
	}

	/**
	 * Set the namespace in the appropriate configuration files.
	 *
	 * @return void
	 */
	protected function setConfigNamespaces()
	{
		$this->setAuthConfigNamespace();

		$this->setNamespaceConfigNamespace();
	}

	/**
	 * Set the authentication User namespace.
	 *
	 * @return void
	 */
	protected function setAuthConfigNamespace()
	{
		$contents = $this->files->get($path = $this->getAuthConfigPath());

		$this->files->put($path, str_replace(
			$this->root().'\\User', $this->argument('name').'\\User', $contents
		));
	}

	/**
	 * Set the namespace configuration file namespaces.
	 *
	 * @return void
	 */
	protected function setNamespaceConfigNamespace()
	{
		$contents = $this->files->get($path = $this->getNamespaceConfigPath());

		$this->files->put($path, str_replace(
			$this->root().'\\', $this->argument('name').'\\', $contents
		));
	}

	/**
	 * Get the root namespace for the application.
	 *
	 * @return string
	 */
	protected function root()
	{
		return $this->laravel['config']['namespaces.root'];
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
	 * Get the path to the namespace configuration file.
	 *
	 * @return string
	 */
	protected function getNamespaceConfigPath()
	{
		return $this->laravel['path.config'].'/namespaces.php';
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
