<?php namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class CommandMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:command';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new command class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Command';

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	public function fire()
	{
		parent::fire();

		if ($this->option('handler'))
		{
			$this->call('handler:command', [
				'name' => $this->argument('name').'Handler',
				'--command' => $this->parseName($this->argument('name')),
			]);
		}
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('queued') && $this->option('handler'))
		{
			return __DIR__.'/stubs/command-queued-with-handler.stub';
		}
		elseif ($this->option('queued'))
		{
			return __DIR__.'/stubs/command-queued.stub';
		}
		elseif ($this->option('handler'))
		{
			return __DIR__.'/stubs/command-with-handler.stub';
		}
		else
		{
			return __DIR__.'/stubs/command.stub';
		}
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace.'\Commands';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('handler', null, InputOption::VALUE_NONE, 'Indicates that handler class should be generated.'),

			array('queued', null, InputOption::VALUE_NONE, 'Indicates that command should be queued.'),
		);
	}

}
