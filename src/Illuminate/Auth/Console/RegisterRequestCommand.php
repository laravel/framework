<?php namespace Illuminate\Auth\Console;

use Illuminate\Console\GeneratorCommand;

class RegisterRequestCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'auth:register-request';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a stub registration form request';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Request';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return __DIR__.'/stubs/register.request.stub';
	}

	/**
	 * Get the desired class name from the input.
	 *
	 * @return string
	 */
	protected function getNameInput()
	{
		return $this->getAppNamespace().'Http\Requests\Auth\RegisterRequest';
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [];
	}

}
