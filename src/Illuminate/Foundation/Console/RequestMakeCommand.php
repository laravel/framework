<?php namespace Illuminate\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RequestMakeCommand extends GeneratorCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:request';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new form request class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Request';

	/**
	 * Set the configuration key for the namespace.
	 *
	 * @var string
	 */
	protected $configKey = 'requests';

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		return $this->option('messages') ?
			__DIR__ . '/stubs/request_with_messages.stub' :
			__DIR__ . '/stubs/request.stub';
	}

	protected function getOptions()
	{
		return [
			['messages', 'm', InputOption::VALUE_NONE, 'add messages methods to your request']
		];
	}
}
