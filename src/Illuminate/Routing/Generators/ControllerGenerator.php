<?php namespace Illuminate\Routing\Generators;

use Illuminate\Filesystem\Filesystem;

class ControllerGenerator {

	/**
	 * The filesystem instance.
	 *
	 * @var \Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The default resource controller methods.
	 *
	 * @var array
	 */
	protected $defaults = array(
		'index',
		'create',
		'store',
		'show',
		'edit',
		'update',
		'destroy'
	);

	/**
	 * Create a new controller generator instance.
	 *
	 * @param  \Illuminate\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Create a new resourceful controller file.
	 *
	 * @param  string  $controller
	 * @param  string  $path
	 * @param  array   $options
	 * @return void
	 */
	public function make($controller, $path, array $options = array())
	{
		$stub = $this->addMethods($this->getController($controller), $options);

		if ( ! $this->files->exists($fullPath = $path."/{$controller}.php"))
		{
			return $this->files->put($fullPath, $stub);
		}

		return false;
	}

	/**
	 * Get the controller class stub.
	 *
	 * @param  string  $controller
	 * @return string
	 */
	protected function getController($controller)
	{
		$stub = $this->files->get(__DIR__.'/stubs/controller.php');

		return str_replace('{{class}}', $controller, $stub);
	}

	/**
	 * Add the method stubs to the controller.
	 *
	 * @param  string  $stub
	 * @param  array   $options
	 * @return string
	 */
	protected function addMethods($stub, array $options)
	{
		// Once we have the applicable methods, we can just spin through those methods
		// and add each one to our array of method stubs. Then we will implode them
		// them all with end-of-line characters and return the final joined list.
		$stubs = $this->getMethodStubs($options);

		$methods = implode(PHP_EOL.PHP_EOL, $stubs);

		return str_replace('{{methods}}', $methods, $stub);
	}

	/**
	 * Get all of the method stubs for the given options.
	 *
	 * @param  array  $options
	 * @return array
	 */
	protected function getMethodStubs($options)
	{
		$stubs = array();

		// Each stub is conveniently kept in its own file so we can just grab the ones
		// we need from disk to build the controller file. Once we have them all in
		// an array we will return this list of methods so they can be joined up.
		foreach ($this->getMethods($options) as $method)
		{
			$stubs[] = $this->files->get(__DIR__."/stubs/{$method}.php");
		}

		return $stubs;
	}

	/**
	 * Get the applicable methods based on the options.
	 *
	 * @param  array  $options
	 * @return array
	 */
	protected function getMethods($options)
	{
		if (isset($options['only']) and count($options['only']) > 0)
		{
			return $options['only'];
		}
		elseif (isset($options['except']) and count($options['except']) > 0)
		{
			return array_diff($this->defaults, $options['except']);
		}

		return $this->defaults;
	}

}