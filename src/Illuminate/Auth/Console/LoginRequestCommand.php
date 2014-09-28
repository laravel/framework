<?php namespace Illuminate\Auth\Console;

use Illuminate\Console\GeneratorCommand;

class LoginRequestCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'auth:login-request';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a stub login form request';

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
		return __DIR__.'/stubs/login.request.stub';
	}

	/**
	 * Get the desired class name from the input.
	 *
	 * @return string
	 */
	protected function getNameInput()
	{
		return $this->getAppNamespace().'Http\Requests\Auth\LoginRequest';
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
