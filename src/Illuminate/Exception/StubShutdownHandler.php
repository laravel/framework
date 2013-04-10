<?php namespace Illuminate\Exception;

use Symfony\Component\HttpKernel\Debug\ExceptionHandler;

class StubShutdownHandler extends ExceptionHandler {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 * Create a new stub shutdown handler.
	 *
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Handle the given exception.
	 *
	 * @param  Exception  $exception
	 * @return void
	 */
	public function handle(\Exception $exception)
	{
		return call_user_func($this->app['exception.function'], $exception);
	}

}