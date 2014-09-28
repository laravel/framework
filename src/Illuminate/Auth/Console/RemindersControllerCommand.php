<?php namespace Illuminate\Auth\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class RemindersControllerCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'auth:reminders-controller';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a stub password reminder controller';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Controller';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		parent::fire();

		$this->comment('Route: $router->controller(\'password\', \''.$this->argument('name')."');");
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/reminders.controller.stub';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		$default = $this->getAppNamespace().'Http\Controllers\Auth\RemindersController';

		return array(
			array('name', InputArgument::OPTIONAL, 'The name of the class', $default),
		);
	}

}
