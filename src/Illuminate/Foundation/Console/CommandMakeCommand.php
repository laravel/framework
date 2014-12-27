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

		if ($this->doesntHaveHandler()) return;

		$this->call('handler:command', [
			'name' => $this->argument('name').'Handler',
			'--command' => $this->parseName($this->argument('name'))
		]);
	}

	/**
	 * Determine if the command doesn't need a handler.
	 *
	 * @return bool
	 */
	protected function doesntHaveHandler()
	{
		return $this->option('self-handling') || $this->option('no-handler');
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('queued') && $this->option('self-handling'))
		{
			return __DIR__.'/stubs/command-self-queued.stub';
		}
		elseif ($this->option('queued'))
		{
			return __DIR__.'/stubs/command-queued.stub';
		}
		elseif ($this->option('self-handling'))
		{
			return __DIR__.'/stubs/command-self.stub';
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
			array('no-handler', null, InputOption::VALUE_NONE, 'Indicates that handler class should not be generated.'),

			array('queued', null, InputOption::VALUE_NONE, 'Indicates that command should be queued.'),

			array('self-handling', null, InputOption::VALUE_NONE, 'Indicates that command handles itself.'),
		);
	}

}
