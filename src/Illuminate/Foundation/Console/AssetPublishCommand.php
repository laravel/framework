<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\AssetPublisher;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AssetPublishCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'asset:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Publish a package's assets to the public directory";

	/**
	 * The asset publisher instance.
	 *
	 * @var Illuminate\Foundation\AssetPublisher
	 */
	protected $assets;

	/**
	 * Create a new asset publish command instance.
	 *
	 * @param  Illuminate\Foundation\AssetPublisher  $assets
	 * @return void
	 */
	public function __construct(AssetPublisher $assets)
	{
		parent::__construct();

		$this->assets = $assets;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$package = $this->getPackage();

		if ( ! is_null($path = $this->getPath()))
		{
			$this->assets->publish($package, $path);
		}
		else
		{
			$this->assets->publishPackage($package);
		}

		$this->output->writeln('<info>Assets published for package:</info> '.$package);
	}

	/**
	 * Get the name of the package being published.
	 *
	 * @return string
	 */
	protected function getPackage()
	{
		if ( ! is_null($package = $this->input->getArgument('package')))
		{
			return $package;
		}
		elseif ( ! is_null($bench = $this->input->getOption('bench')))
		{
			return $bench;
		}

		throw new \Exception("Package or bench must be specified.");
	}

	/**
	 * Get the specified path to the files.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$path = $this->input->getOption('path');

		// First we will check for an explicitly specified path from the user. If one
		// exists we will use that as the path to the assets. This allows the free
		// storage of assets wherever is best for this developer's web projects.
		if ( ! is_null($path))
		{
			return $this->laravel['path.base'].'/'.$path;
		}

		// If a "bench" option was specified, we will publish from a workbench as the
		// source location. This is mainly just a short-cut for having to manually
		// specify the full workbench path using the --path command line option.
		$bench = $this->input->getOption('bench');

		if ( ! is_null($bench))
		{
			return $this->laravel['path.base']."/workbench/{$bench}/public";
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('package', InputArgument::OPTIONAL, 'The name of package being published.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('bench', null, InputOption::VALUE_OPTIONAL, 'The name of the workbench to publish.', null),

			array('path', null, InputOption::VALUE_OPTIONAL, 'The path to the configuration files.', null),
		);
	}

}