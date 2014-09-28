<?php namespace Illuminate\Auth\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class AuthControllerCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'auth:controller';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a stub authentication controller';

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

		$this->comment('Route: $router->controller(\'auth\', \''.$this->argument('name')."');");
	}

	/**
	 * Replace the class name for the given stub.
	 *
	 * @param  string  $stub
	 * @param  string  $name
	 * @return string
	 */
	protected function replaceClass($stub, $name)
	{
		$stub = parent::replaceClass($stub, $name);

		return str_replace(
			'{{request.namespace}}', $this->getAppNamespace().'Http\Requests\\', $stub
		);
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/controller.stub';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		$default = $this->getAppNamespace().'Http\Controllers\Auth\AuthController';

		return array(
			array('name', InputArgument::OPTIONAL, 'The name of the class', $default),
		);
	}

}
