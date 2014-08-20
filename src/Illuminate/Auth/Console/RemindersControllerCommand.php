<?php namespace Illuminate\Auth\Console;

use Illuminate\Filesystem\Filesystem;
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

		$this->comment("Route: Route::controller('password', 'RemindersController');");
	}

	/**
	 * Get the controller class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		return $this->laravel['path.controllers'].'/'.$name.'.php';
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
		return array(
			array('name', InputArgument::OPTIONAL, 'The name of the class', 'RemindersController'),
		);
	}

}
