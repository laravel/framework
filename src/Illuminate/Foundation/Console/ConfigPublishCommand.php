<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\ConfigPublisher;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ConfigPublishCommand extends Command {

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

		if($this->config->alreadyExists($package))
		{
			if( ! $this->confirmOverwrite())
			{
				$this->output->writeln('<info>Configuration not published for package: </info>'.$package);
				return;
			}
		}

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
	 * Confirm before overwriting the configuration
	 *
	 * @return bool
	 */
	protected function confirmOverwrite()
	{
		$force = $this->input->getOption('force');
		$production = strtolower($this->getLaravel()->environment()) == 'production';
		$silent = $this->input->getOption('no-interaction');

		if($silent and $production)
		{
			return $force;
		}
		elseif($production)
		{
			return $this->confirm('Package Config Already Published. Overwrite previous configuration ?');
		}
		else
		{
			return true;
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
			array('force', null, InputOption::VALUE_OPTIONAL, 'Force overwrite already published configuration. Valid with --no-interaction', null),
		);
	}

}
