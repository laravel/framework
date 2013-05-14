<?php namespace Illuminate\Exception;

use Exception;
use Whoops\Run;

class WhoopsDisplayer implements ExceptionDisplayerInterface {

	/**
	 * Create a new Whoops exception displayer.
	 *
	 * @param  \Whoops\Run  $whoops
	 * @return void
	 */
	public function __construct(Run $whoops)
	{
		$this->whoops = $whoops;
	}

	/**
	 * Display the given exception to the user.
	 *
	 * @param  \Exception  $exception
	 */
	public function display(Exception $exception)
	{
		header('HTTP/1.1 500 Internal Server Error');

		$this->whoops->handleException($exception);
	}

}