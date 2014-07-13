<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Foundation\ConfigPublisher;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ConfigPublishCommand extends Command {

	use ConfirmableTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'config:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Publish a package's configuration to the application";

	/**
	 * The config publisher instance.
	 *
	 * @var \Illuminate\Foundation\ConfigPublisher
	 */
	protected $config;

	/**
	 * Create a new configuration publish command instance.
	 *
	 * @param  \Illuminate\Foundation\ConfigPublisher  $config
	 * @return void
	 */
	public function __construct(ConfigPublisher $config)
	{
		parent::__construct();

		$this->config = $config;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$package = $this->input->getArgument('package');

		$proceed = $this->confirmToProceed('Config Already Published!', function() use ($package)
		{
			return $this->config->alreadyPublished($package);
		});

		if ( ! $proceed) return;

		if ( ! is_null($path = $this->getPath()))
		{
			$this->config->publish($package, $path);
		}
		else
		{
			$this->config->publishPackage($package);
		}

		$this->output->writeln('<info>Configuration published for package:</info> '.$package);
	}

	/**
	 * Get the specified path to the files.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$path = $this->input->getOption('path');

		if ( ! is_null($path))
		{
			return $this->laravel['path.base'].'/'.$path;
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
			array('package', InputArgument::REQUIRED, 'The name of the package being published.'),
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
			array('path', null, InputOption::VALUE_OPTIONAL, 'The path to the configuration files.', null),

			array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when the file already exists.'),
		);
	}

}
